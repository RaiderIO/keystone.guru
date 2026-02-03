<?php

namespace App\Service\Wowhead;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Service\Traits\Curl;
use App\Service\Wowhead\Logging\WowheadTranslationServiceLoggingInterface;
use Illuminate\Support\Collection;

class WowheadTranslationService implements WowheadTranslationServiceInterface
{
    use Curl;

    private const string IDENTIFYING_TOKEN_DUNGEON_NAMES = "new Listview({template: 'zone', id: 'zones', extraCols: ['popularity']";
    private const string IDENTIFYING_TOKEN_ZONE_NAMES    = 'var g_zone_areas = ';

    private const array LOCALE_URL_MAPPING = [
        'en_US' => '',
        'ko_KR' => 'ko/',
        'fr_FR' => 'fr/',
        'de_DE' => 'de/',
        'zh_CN' => 'cn/',
        'zh_TW' => 'tw/',
        'es_ES' => 'es/',
        'es_MX' => 'mx/',
        'ru_RU' => 'ru/',
        'pt_BR' => 'pt/',
        'it_IT' => 'it/',
    ];

    private const array EXPANSION_URL_MAPPING = [
        Expansion::EXPANSION_CLASSIC      => 'classic',
        Expansion::EXPANSION_TBC          => 'burning-crusade',
        Expansion::EXPANSION_WOTLK        => 'wrath',
        Expansion::EXPANSION_CATACLYSM    => 'cataclysm',
        Expansion::EXPANSION_MOP          => 'mists',
        Expansion::EXPANSION_WOD          => 'warlords',
        Expansion::EXPANSION_LEGION       => 'legion',
        Expansion::EXPANSION_BFA          => 'bfa',
        Expansion::EXPANSION_SHADOWLANDS  => 'shadowlands',
        Expansion::EXPANSION_DRAGONFLIGHT => 'dragonflight',
        Expansion::EXPANSION_TWW          => 'war-within',
        Expansion::EXPANSION_MIDNIGHT     => 'midnight',
        Expansion::EXPANSION_TLT          => 'last-titan',
    ];

    public function __construct(private WowheadTranslationServiceLoggingInterface $log)
    {
    }

    public function getNpcNames(GameVersion $gameVersion): Collection
    {
        $env = match ($gameVersion->key) {
            GameVersion::GAME_VERSION_RETAIL      => '1',
            GameVersion::GAME_VERSION_BETA        => '3',
            GameVersion::GAME_VERSION_CLASSIC_ERA => '4',
            GameVersion::GAME_VERSION_WRATH       => '8',
            default                               => null,
        };

        $result = collect();
        if ($env !== null) {
            $data = $this->curlGet(sprintf('https://nether.wowhead.com/data/npc-names?dataEnv=%s', $env));

            $dataArr = json_decode($data, true);

            $result = collect();

            foreach ($dataArr as $npcNames) {
                /** @var array{
                 *     id: int,
                 *     name_enus: string,
                 *     name_frfr: string,
                 *     name_dede: string,
                 *     name_eses: string,
                 *     name_ptbr: string,
                 *     name_ruru: string,
                 *     name_itit: string,
                 *     name_zhcn: string,
                 *     name_kokr: string,
                 *     name_zhtw: string,
                 *     name_esmx: string
                 * } $npcNames
                 */

                $npcId = $npcNames['id'];

                foreach ($npcNames as $nameLocale => $npcName) {
                    if ($nameLocale === 'id') {
                        continue;
                    }

                    $locale = str_replace('name_', '', $nameLocale);
                    $parts  = str_split($locale, 2);                               // e.g., enus -> ['en', 'us']
                    $locale = sprintf('%s_%s', $parts[0], strtoupper($parts[1])); // en_US, fr_FR, etc.

                    $result->put($locale, $result->get($locale, collect())
                        ->put($npcId, $npcName ?? ''));
                }
            }
        }

        return $result;
    }

    public function getSpellNames(GameVersion $gameVersion): Collection
    {
        $env = match ($gameVersion->key) {
            GameVersion::GAME_VERSION_RETAIL      => '1',
            GameVersion::GAME_VERSION_BETA        => '3',
            GameVersion::GAME_VERSION_CLASSIC_ERA => '4',
            GameVersion::GAME_VERSION_WRATH       => '8',
            default                               => null,
        };

        if ($env === null) {
            return collect();
        }

        $result = collect();
        foreach (config('language.all') as $language) {
            $locale = $language['long'];

            if ($locale === 'ho_HO' || ($language['ai'] ?? false)) {
                continue; // Skip Hodor language or AI languages
            }

            $parts = explode('_', (string)$locale);
            if (count($parts) !== 2) {
                continue; // Skip invalid locales
            }

            $wowheadLocale = match ($locale) {
                'ko_KR' => '1',
                'fr_FR' => '2',
                'de_DE' => '3',
                'zh_CN', 'zh_TW' => '4',
                'es_ES', 'es_MX' => '6',
                'ru_RU' => '7',
                'pt_BR' => '8',
                'it_IT' => '9',
                default => '0', // en_US, uk_UA
            };

            $data = $this->curlGet(sprintf('https://nether.wowhead.com/data/spell-names?dataEnv=%d&locale=%d', $env, $wowheadLocale));

            $dataArr = json_decode($data, true);

            foreach ($dataArr as $spellData) {
                /** @var array{
                 *     id: int,
                 *     school: int,
                 *     icon: string,
                 *     name: string,
                 *     rank: string|null
                 * } $spellData
                 */
                $result->put($locale, $result->get($locale, collect())
                    ->put($spellData['id'], $spellData['name']));
            }
        }

        return $result;
    }

    public function getDungeonNames(): Collection
    {
        $this->log->getDungeonNamesStart();

        try {
            $dungeons = Dungeon::all()
                ->keyBy('zone_id');

            $result = [];
            foreach (self::LOCALE_URL_MAPPING as $locale => $wowheadLocale) {
                $this->log->getDungeonNamesLocaleStart($locale);

                try {
                    $url = sprintf('https://wowhead.com/%szones/instances', $wowheadLocale);
                    $this->log->getDungeonNamesWowheadUrl($url);

                    $response = $this->curlGet($url);

                    $lines = explode(PHP_EOL, $response);
                    foreach ($lines as $line) {
                        if (str_contains($line, self::IDENTIFYING_TOKEN_DUNGEON_NAMES)) {
                            $json = $this->correctMalformedJson($line);
                            foreach ($json['data'] as $dungeonData) {
                                /** @var Dungeon|null $dungeon */
                                $dungeon = $dungeons->get($dungeonData['id']);
                                if ($dungeon) {
                                    $result[$locale][$dungeon->id] = $dungeonData['name'];

                                    $this->log->getDungeonNamesSetDungeonName($dungeon->key, $dungeonData['name']);
                                }
                            }

                            ksort($result[$locale]);

                            break; // No need to continue looping lines
                        }
                    }
                } finally {
                    $this->log->getDungeonNamesLocaleEnd();
                }
            }
        } finally {
            $this->log->getDungeonNamesEnd();
        }

        return collect($result);
    }

    public function getFloorNames(): Collection
    {
        $result = [];
        foreach (self::LOCALE_URL_MAPPING as $locale => $wowheadLocale) {
            $response = $this->curlGet(sprintf('https://nether.wowhead.com/%sdata/zones', $wowheadLocale));

            $lines = explode(PHP_EOL, $response);
            foreach ($lines as $line) {
                if (str_contains($line, self::IDENTIFYING_TOKEN_ZONE_NAMES)) {
                    $json = substr($line, strlen(self::IDENTIFYING_TOKEN_ZONE_NAMES));
                    // Remove );
                    $json = rtrim($json, ');');
                    $data = json_decode($json, true);

                    foreach ($data as $zoneId => &$zoneData) {
                        // Make the keys uniform
                        $zoneData = array_values($zoneData);
                    }

                    $result[$locale] = $data;
                }
            }
        }

        return collect($result);
    }

    /**
     * The below is not valid JSON, this will need to be corrected.
     *
     * {
     * template: 'zone',
     * id: 'zones',
     * extraCols: ['popularity'],
     * sort: ["popularity"],
     * maxPopularity: 481,
     * hiddenCols: ['territory'],
     * data: [{
     *
     * @param  string     $line
     * @return array|null
     */
    private function correctMalformedJson(string $line): ?array
    {
        $jsObject = substr($line, strlen('new Listview('));
        $jsObject = rtrim($jsObject, ');');

        // This $jsObject contains some issues that we need to fix before we can decode it as JSON
        // 1) Unquoted keys
        // 2) Single quotes instead of double quotes

        // Find the part that ends with "data: "
        $dataPos = strpos($jsObject, 'data: ');
        if ($dataPos === false) {
            return null; // No data found, skip
        }
        $headersStr = substr($jsObject, 0, $dataPos);
        // Remove the trailing comma
        $headersStr = trim($headersStr, PHP_EOL . ', ');
        // Append a closing brace to make it a "valid" JSON object
        $headersStr .= '}';

        $headersJson   = $this->jsObjectToJson($headersStr);
        $parsedHeaders = json_decode($headersJson, true);

        $dataStr    = substr($jsObject, $dataPos + strlen('data: '));
        $parsedData = json_decode(sprintf('{"data": %s', $dataStr), true);

        return array_merge($parsedHeaders, $parsedData);
    }

    private function jsObjectToJson(string $s): string
    {
        // 1) Quote unquoted object keys: foo: -> "foo":
        $s = preg_replace('/([{\s,])([A-Za-z_][A-Za-z0-9_]*)\s*:/', '$1"$2":', $s);

        // 2) Convert single-quoted strings to double-quoted, escaping inner quotes/backslashes.
        $s = preg_replace_callback("/'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/", function ($m) {
            $inner = $m[1];
            // Unescape any escaped single quotes first, then escape backslashes and double quotes for JSON
            $inner = str_replace([
                "\\'",
                "\\\\",
            ], [
                "'",
                "\\\\",
            ], $inner);
            $inner = str_replace([
                '\\',
                '"',
            ], [
                '\\\\',
                '\\"',
            ], $inner);

            return '"' . $inner . '"';
        }, (string)$s);

        // 3) Remove trailing commas before } or ] (common in JS, invalid in JSON)
        $s = preg_replace('/,\s*([\]}])/', '$1', (string)$s);

        return $s;
    }
}
