<?php

namespace App\Service\Wowhead;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\Traits\Curl;
use App\Service\Wowhead\Logging\WowheadTranslationServiceLoggingInterface;
use Illuminate\Support\Collection;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ContentLengthException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;
use PHPHtmlParser\Options;
use Str;

class WowheadTranslationService implements WowheadTranslationServiceInterface
{
    use Curl;

    private const string IDENTIFYING_TOKEN_ZONE_NAMES = 'var g_zone_areas = ';

    private const string IDENTIFYING_TOKEN_CONTINENT_NAMES = "WH.setPageData('maps.continents', ";

    private const string LISTVIEW_PATTERN = '/<script[^>]*id="data\\.page\\.listPage\\.listviews"[^>]*>(.*?)<\\/script>/s';

    /** Wowhead answers its zone pages with a 403 bot challenge for a full browser user agent, but not for this one. */
    private const string ZONE_PAGE_USER_AGENT = 'Mozilla/5.0';

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
                        ->put($npcId, $npcName));
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
                'ko_KR'          => '1',
                'fr_FR'          => '2',
                'de_DE'          => '3',
                'zh_CN', 'zh_TW' => '4',
                'es_ES', 'es_MX' => '6',
                'ru_RU'          => '7',
                'pt_BR'          => '8',
                'it_IT'          => '9',
                default          => '0', // en_US, uk_UA
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
            $dungeons = Dungeon::query()
                ->where('zone_id', '>', 0)
                ->get()
                ->keyBy('zone_id');

            $result = [];
            foreach (self::LOCALE_URL_MAPPING as $locale => $wowheadLocale) {
                $this->log->getDungeonNamesLocaleStart($locale);

                try {
                    $url = sprintf('https://wowhead.com/%szones/instances', $wowheadLocale);
                    $this->log->getDungeonNamesWowheadUrl($url);

                    $response = $this->curlGet($url, [CURLOPT_USERAGENT => self::ZONE_PAGE_USER_AGENT]);

                    $response = Str::replace('data.page.listPage.listviews', 'dataPageListPageListviews', $response);

                    $dom = new Dom();
                    $dom->loadStr($response, new Options()->setRemoveScripts(false));

//                    /** @var Dom\Node\AbstractNode $scriptElement */
//                    $scriptElement = $dom->find('script');

                    /** @var Dom\Node\AbstractNode|null $scriptElement */
                    $scriptElement = $dom->getElementById('dataPageListPageListviews');
                    if ($scriptElement === null) {
                        $this->log->getDungeonNamesElementNotFound();

                        continue;
                    }

                    $json = json_decode($scriptElement->innerhtml, true);
                    if (is_array($json)) {
                        foreach ($json[0]['data'] as $dungeonData) {
                            /** @var Dungeon|null $dungeon */
                            $dungeon = $dungeons->get($dungeonData['id']);
                            if ($dungeon) {
                                $result[$locale][$dungeon->id] = $dungeonData['name'];

                                $this->log->getDungeonNamesSetDungeonName($dungeon->key, $dungeonData['name']);
                            }
                        }

                        ksort($result[$locale]);
                    } else {
                        $this->log->getDungeonNamesInvalidJson();
                    }
                } catch (CircularException|ContentLengthException|LogicalException|StrictException) {
                    $this->log->getDungeonNamesMalformedHtml();
                } catch (ChildNotFoundException|NotLoadedException) {
                    $this->log->getDungeonNamesElementNotFound();
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

    public function getZoneNames(GameVersion $gameVersion): Collection
    {
        $gameVersionPath = match ($gameVersion->key) {
            GameVersion::GAME_VERSION_RETAIL      => '',
            GameVersion::GAME_VERSION_CLASSIC_ERA => 'classic/',
            default                               => null,
        };

        $result = collect();
        if ($gameVersionPath === null) {
            return $result;
        }

        foreach (self::LOCALE_URL_MAPPING as $locale => $wowheadLocale) {
            $response = $this->curlGet(
                sprintf('https://www.wowhead.com/%s%szones', $gameVersionPath, $wowheadLocale),
                [CURLOPT_USERAGENT => self::ZONE_PAGE_USER_AGENT],
            );

            if (preg_match(self::LISTVIEW_PATTERN, $response, $matches) !== 1) {
                continue;
            }

            $listviews = json_decode($matches[1], true);
            if (!is_array($listviews)) {
                continue;
            }

            $zoneNames = [];
            foreach ($listviews[0]['data'] ?? [] as $zoneData) {
                /** @var array{id: int, name: string} $zoneData */
                $zoneNames[$zoneData['id']] = $zoneData['name'];
            }

            ksort($zoneNames);
            $result->put($locale, $zoneNames);
        }

        return $result;
    }

    public function getContinentNames(): Collection
    {
        $result = collect();
        foreach (self::LOCALE_URL_MAPPING as $locale => $wowheadLocale) {
            $response = $this->curlGet(sprintf('https://nether.wowhead.com/%sdata/zones', $wowheadLocale));

            $start = strpos($response, self::IDENTIFYING_TOKEN_CONTINENT_NAMES);
            if ($start === false) {
                continue;
            }

            $start += strlen(self::IDENTIFYING_TOKEN_CONTINENT_NAMES);
            $end = strpos($response, '}', $start);
            if ($end === false) {
                continue;
            }

            $continentNames = json_decode(substr($response, $start, $end - $start + 1), true);
            if (is_array($continentNames)) {
                $result->put($locale, $continentNames);
            }
        }

        return $result;
    }
}
