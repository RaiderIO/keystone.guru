<?php

namespace App\Console\Commands\Localization\Zone;

use App\Console\Commands\Localization\BaseSyncCommand;
use App\Console\Commands\Localization\Traits\ExportsTranslations;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Service\WagoTools\GameLocale;
use App\Service\WagoTools\WagoToolsServiceInterface;
use App\Service\Wowhead\WowheadTranslationServiceInterface;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SyncZoneNames extends BaseSyncCommand
{
    use ExportsTranslations;

    public const string SOURCE_UI_MAP_GROUP_MEMBER    = 'UiMapGroupMember';
    public const string SOURCE_UI_MAP                 = 'UiMap';
    public const string SOURCE_MAP                    = 'Map';
    public const string SOURCE_AREA_TABLE             = 'AreaTable';
    public const string SOURCE_LFG_DUNGEONS           = 'LfgDungeons';
    public const string SOURCE_CLASSIC_ERA_UI_MAP     = 'ClassicEraUiMap';
    public const string SOURCE_ANNIVERSARY_LFG        = 'AnniversaryLfgDungeons';
    public const string SOURCE_DIFFICULTY             = 'Difficulty';
    public const string SOURCE_CLASSIC_ERA_DIFFICULTY = 'ClassicEraDifficulty';

    /** A source whose localized names match its English ones this often has no client in that locale. */
    private const float UNTRANSLATED_SOURCE_RATIO = 0.9;

    const array EXCLUDE_DUNGEONS = [
        DungeonKey::SCARLET_MONASTERY_ARMORY->value,
        DungeonKey::SCARLET_MONASTERY_CATHEDRAL->value,
        DungeonKey::SCARLET_MONASTERY_GRAVEYARD->value,
        DungeonKey::SCARLET_MONASTERY_LIBRARY->value,

        DungeonKey::DIRE_MAUL_EAST->value,
        DungeonKey::DIRE_MAUL_NORTH->value,
        DungeonKey::DIRE_MAUL_WEST->value,

        DungeonKey::MECHAGON_JUNKYARD->value,
        DungeonKey::MECHAGON_WORKSHOP->value,

        DungeonKey::DAWN_OF_THE_INFINITE_GALAKRONDS_FALL->value,
        DungeonKey::DAWN_OF_THE_INFINITE_MUROZONDS_RISE->value,

        DungeonKey::TAZAVESH_SO_LEAHS_GAMBIT->value,
        DungeonKey::TAZAVESH_STREETS_OF_WONDER->value,
    ];

    /**
     * Continent dungeons whose floors are open-world zones rather than instance areas, by the Wowhead continent key
     * that holds the continent's own name.
     */
    const array CONTINENT_DUNGEONS = [
        DungeonKey::KALIMDOR->value         => 'POSTMASTER_PIPE_KALIMDOR',
        DungeonKey::EASTERN_KINGDOMS->value => 'POSTMASTER_PIPE_EASTERNKINGDOMS',
    ];

    /**
     * The DB2 tables the game's own zone names are read from, in the order they are tried when a name is looked
     * up by its English text. UiMapGroupMember holds the names of dungeon floors, so it goes first. The overworld
     * floors of the classic continents only exist as Classic Era UI maps (e.g. "The Barrens"), and the separate
     * Scarlet Monastery and Dire Maul wings only in the dungeon finder of the Classic clients. Difficulty holds the
     * raid sizes ("10 Player"); 20 player raids only exist in Classic.
     *
     * @var array<string, array{product: string, table: string, idColumn: string, nameColumn: string}>
     */
    private const array GAME_DATA_NAME_SOURCES = [
        self::SOURCE_UI_MAP_GROUP_MEMBER    => ['product' => 'wow', 'table' => 'UiMapGroupMember', 'idColumn' => 'UiMapID', 'nameColumn' => 'Name_lang'],
        self::SOURCE_UI_MAP                 => ['product' => 'wow', 'table' => 'UiMap', 'idColumn' => 'ID', 'nameColumn' => 'Name_lang'],
        self::SOURCE_MAP                    => ['product' => 'wow', 'table' => 'Map', 'idColumn' => 'ID', 'nameColumn' => 'MapName_lang'],
        self::SOURCE_AREA_TABLE             => ['product' => 'wow', 'table' => 'AreaTable', 'idColumn' => 'ID', 'nameColumn' => 'AreaName_lang'],
        self::SOURCE_LFG_DUNGEONS           => ['product' => 'wow', 'table' => 'LFGDungeons', 'idColumn' => 'ID', 'nameColumn' => 'Name_lang'],
        self::SOURCE_CLASSIC_ERA_UI_MAP     => ['product' => 'wow_classic_era', 'table' => 'UiMap', 'idColumn' => 'ID', 'nameColumn' => 'Name_lang'],
        self::SOURCE_ANNIVERSARY_LFG        => ['product' => 'wow_anniversary', 'table' => 'LFGDungeons', 'idColumn' => 'ID', 'nameColumn' => 'Name_lang'],
        self::SOURCE_DIFFICULTY             => ['product' => 'wow', 'table' => 'Difficulty', 'idColumn' => 'ID', 'nameColumn' => 'Name_lang'],
        self::SOURCE_CLASSIC_ERA_DIFFICULTY => ['product' => 'wow_classic_era', 'table' => 'Difficulty', 'idColumn' => 'ID', 'nameColumn' => 'Name_lang'],
    ];

    /**
     * en_US names that are our own wording of a zone the game names differently.
     */
    private const array GAME_DATA_NAME_ALIASES = [
        'Orgrimmar (Horrific Vision)' => 'Horrific Vision of Orgrimmar',
        'Stormwind (Horrific Vision)' => 'Horrific Vision of Stormwind',
        '10-man'                      => '10 Player',
        '20-man'                      => '20 Player',
        '25-man'                      => '25 Player',
        '40-man'                      => '40 Player',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:synczonenames
                            {--overwrite : Replace existing dungeon and floor names with the name the game data has for them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the names of all zones from Wowhead and updates the localizations.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(
        WowheadTranslationServiceInterface $wowheadTranslationService,
        WagoToolsServiceInterface          $wagoToolsService,
    ): void {
        $updatedTranslations = $this->syncDungeonNames($wowheadTranslationService);

        $updatedTranslations = $this->syncFloorNames($wowheadTranslationService, $updatedTranslations);

        $updatedTranslations = $this->syncContinentNames($wowheadTranslationService, $updatedTranslations);

        $updatedTranslations = $this->syncGameDataNames($wagoToolsService, $updatedTranslations, (bool)$this->option('overwrite'));

        $this->saveTranslationsToDisk($updatedTranslations);
    }

    /**
     * Dungeons keyed by their zone ID. Dungeons without one (the classic continents, the Horrific Visions)
     * are left out: they would all collide on zone ID 0, and no Wowhead zone matches them anyway.
     *
     * @return Collection<int, Dungeon>
     */
    public function getDungeonsByZoneId(): Collection
    {
        return Dungeon::with(['expansion', 'floors'])
            ->where('zone_id', '>', 0)
            ->get()
            ->keyBy('zone_id');
    }

    /**
     * @return array<string, mixed>
     */
    private function syncDungeonNames(WowheadTranslationServiceInterface $wowheadTranslationService): array
    {
        $dungeonNamesByLocale = $wowheadTranslationService->getDungeonNames();

        $dungeonsById = Dungeon::with('expansion')->get()
            ->keyBy('id');

        // Get the existing spell names from the localization file and merge with the fetched names
        $updatedTranslations = [];
        foreach ($dungeonNamesByLocale as $locale => $dungeonNamesForLocale) {
            $existingTranslations = __('dungeons', [], $locale);
            if (!is_array($existingTranslations) || empty($existingTranslations)) {
                $existingTranslations = [];
            }

            foreach ($dungeonNamesForLocale as $dungeonId => $dungeonName) {
                /** @var Dungeon $dungeon */
                $dungeon = $dungeonsById->get($dungeonId);

                // Skip some zones that we split off compared to the Wowhead data
                if (in_array($dungeon->key, self::EXCLUDE_DUNGEONS)) {
                    $this->comment(sprintf('- Skipping excluded dungeon %s', $dungeon->key));
                    $this->warn(sprintf('-- Got name "%s" for locale %s', $dungeonName, $locale));
                    continue;
                }

                // Ensure expansion array is set
                if (!isset($updatedTranslations[$locale][$dungeon->expansion->shortname])) {
                    $updatedTranslations[$locale][$dungeon->expansion->shortname] = [];
                }

                $dungeonTranslationKey = explode('.', $dungeon->name)[2];
                // Only if we didn't have a translation yet for this dungeon, we add it
                // This way we can make manual corrections that won't be overwritten
                if (empty($existingTranslations[$dungeon->expansion->shortname][$dungeonTranslationKey]['name'])) {
                    $updatedTranslations[$locale][$dungeon->expansion->shortname][$dungeonTranslationKey]['name'] = $dungeonName;
                }
            }

            $updatedTranslations[$locale] = array_replace_recursive($existingTranslations, $updatedTranslations[$locale]);
        }

        return $updatedTranslations;
    }

    /**
     * @param  array<string, mixed> $existingTranslationsByLocale
     * @return array<string, mixed>
     */
    private function syncFloorNames(WowheadTranslationServiceInterface $wowheadTranslationService, array $existingTranslationsByLocale): array
    {
        $floorNamesByLocale = $wowheadTranslationService->getFloorNames();
        $dungeonsByZoneId   = $this->getDungeonsByZoneId();

        $englishFloorNames = $floorNamesByLocale->get('en_US', []);
        if (empty($englishFloorNames)) {
            $this->error('No zone names found for en_US locale. Please check the Wowhead data.');

            return [];
        }

        // 1. Build up a mapping of zone IDs to their names, and where to find them.
        //    The zone/floor names don't match up exactly with the Wowhead data, so we need to
        //    construct a mapping of zone IDs/index to their names. To do this, we use the English names
        //    to find the zone IDs+index and then use those to find the names in the other locales.
        $zoneIdIndexReference = collect();
        foreach ($englishFloorNames as $zoneId => $floorNames) {
            // Find the KSG floor that this translation belongs to
            /** @var Dungeon|null $dungeon */
            $dungeon = $dungeonsByZoneId->get($zoneId);
            if (!($dungeon instanceof Dungeon)) {
                // We don't care - there's many zones that we don't have a dungeon for
                // $this->error(sprintf('No dungeon found for zone ID %d', $zoneId));
                continue;
            }

            // Skip some zones that we split off compared to the Wowhead data
            if (in_array($dungeon->key, self::EXCLUDE_DUNGEONS)) {
                $this->comment(sprintf('- Skipping excluded dungeon %s for zone ID %d', $dungeon->key, $zoneId));
                continue;
            }

            $dungeonZoneIdIndexReference = [];
            // The zone name is an array of names for each floor, so we need to extract them
            foreach ($floorNames as $floorIndex => $floorName) {
                $found = false;
                foreach ($dungeon->floors as $floor) {
                    if ($this->normalizeFloorName($floorName) === $this->normalizeFloorName(__($floor->name, [], 'en_US'))) {
                        // We found the KSG floor for this name, so we can store where to find it in $dungeonZoneIdIndexReference
                        $dungeonZoneIdIndexReference[$floor->id] = [
                            'index' => $floorIndex,
                            // Extract the translation name key from the floor name
                            // 0. dungeons
                            // 1. bfa
                            // 2. atal_dazar
                            // 3. floors
                            // 4. sacrificial_pits <-- looking for this one
                            'translationKey' => explode('.', $floor->name)[4],
                        ];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $this->warn(sprintf('No floor found for zone ID %d and name "%s"', $zoneId, $floorName));
                }
            }

            // If we have a facade floor, save the facade floor's name as the dungeon name
            /** @var Floor|null $facadeFloor */
            $facadeFloor = $dungeon->floors->firstWhere('facade', true);
            if ($facadeFloor !== null) {
                $dungeonZoneIdIndexReference[$facadeFloor->id] = [
                    // Facade floor is always the last floor (one will be added shortly so this will match up)
                    'index' => count($floorNames),
                    // Extract the translation key from the facade floor name
                    'translationKey' => explode('.', $facadeFloor->name)[4],
                ];
            }

            // Save the zoneID and index reference for the dungeon to the global reference
            $zoneIdIndexReference->put($zoneId, $dungeonZoneIdIndexReference);
        }

        // 2. For all dungeons that we have, but are not in the mapping, at least add the dungeon name as the floor name
        foreach ($dungeonsByZoneId as $zoneId => $dungeon) {
            if (!$zoneIdIndexReference->has($zoneId)) {
                // Skip some zones that we split off compared to the Wowhead data
                if (in_array($dungeon->key, self::EXCLUDE_DUNGEONS)) {
                    $this->comment(sprintf('- Skipping excluded dungeon %s for zone ID %d', $dungeon->key, $zoneId));
                    continue;
                }

                $dungeonZoneIdIndexReference = [];

                /** @var Floor $floor */
                $floor                                   = $dungeon->floors->first();
                $dungeonZoneIdIndexReference[$floor->id] = [
                    // 0 based if the dungeon was not found in the Wowhead data
                    'index' => 0,
                    // Extract the translation key from the facade floor name
                    'translationKey' => explode('.', $floor->name)[4],
                ];

                // Save the zoneID and index reference for the dungeon to the global reference
                $zoneIdIndexReference->put($zoneId, $dungeonZoneIdIndexReference);
                $this->info(sprintf('Added missing dungeon %s for zone ID %d to the zone ID index reference', $dungeon->key, $zoneId));
            }
        }

        // 3. Based on this mapping, we can now construct the translation array for each locale and save it to disk
        foreach ($floorNamesByLocale as $locale => $floorNamesForLocale) {
            /** @var array<int, array<int, string>> $floorNamesForLocale */

            // Now match the zone IDs to the dungeon and construct the translation array
            $updatedTranslations = [];

            foreach ($zoneIdIndexReference as $zoneId => $floorData) {
                if ($dungeonsByZoneId->has($zoneId)) {
                    /** @var Dungeon $dungeon */
                    $dungeon = $dungeonsByZoneId->get($zoneId);

                    // Extract the translation name key from the floor name
                    // 0. dungeons
                    // 1. bfa
                    // 2. atal_dazar <-- looking for this one
                    $dungeonTranslationKey = explode('.', $dungeon->name)[2];

                    // Add the facade floor name to the list of floor names "retrieved" from Wowhead so we can resolve facade floor names
                    $floorNamesForLocale[$zoneId][] = $existingTranslationsByLocale[$locale][$dungeon->expansion->shortname][$dungeonTranslationKey]['name'] ?? '';

                    foreach ($floorData as $floorId => $data) {
                        if (empty($floorNamesForLocale[$zoneId][$data['index']])) {
                            $this->warn(sprintf('No floor name found for zone ID %d and index %d in locale %s', $zoneId, $data['index'], $locale));
                            continue;
                        }

                        // Only if we didn't have a translation yet for this floor, we add it
                        // This way we can make manual corrections that won't be overwritten
                        if (empty($existingTranslationsByLocale[$locale][$dungeon->expansion->shortname][$dungeonTranslationKey]['floors'][$data['translationKey']])) {
                            $updatedTranslations[$dungeon->expansion->shortname][$dungeonTranslationKey]['floors'][$data['translationKey']] = $floorNamesForLocale[$zoneId][$data['index']];
                        }
                    }

//                    if ($dungeon->key === DungeonKey::OPERATION_FLOODGATE->value) {
//                        dd(
//                            $zoneId,
                    ////                            $zoneIdIndexReference,
//                            $updatedTranslations[$dungeon->expansion->shortname][$dungeonTranslationKey],
//                            $floorNamesForLocale[$zoneId],
//                            $floorData
//                        );
//                    }
                }
            }

            // Merge the existing translations with the updated translations
            // This ensures that we don't overwrite existing translations that are not in the Wowhead data
            $existingTranslationsByLocale[$locale] = array_replace_recursive($existingTranslationsByLocale[$locale], $updatedTranslations);

            foreach ($existingTranslationsByLocale[$locale] as &$dungeons) {
                ksort($dungeons);
            }
        }

        return $existingTranslationsByLocale;
    }

    /**
     * Fills the continent dungeons (Kalimdor, Eastern Kingdoms): each floor is an open-world zone, found by its en_US
     * name in Wowhead's retail zone list and, failing that, the classic one ("The Barrens" only exists there). The
     * continent's own name and facade floor come from Wowhead's continent names.
     *
     * @param  array<string, mixed> $existingTranslationsByLocale
     * @return array<string, mixed>
     */
    public function syncContinentNames(
        WowheadTranslationServiceInterface $wowheadTranslationService,
        array                              $existingTranslationsByLocale,
    ): array {
        $continentNamesByLocale = $wowheadTranslationService->getContinentNames();
        $zoneNamesBySource      = [
            $wowheadTranslationService->getZoneNames(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL)),
            $wowheadTranslationService->getZoneNames(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA)),
        ];

        $continentDungeons = Dungeon::with(['expansion', 'floors'])
            ->whereIn('key', array_keys(self::CONTINENT_DUNGEONS))
            ->get();

        foreach ($continentDungeons as $dungeon) {
            /** @var Dungeon $dungeon */
            $expansionKey          = $dungeon->expansion->shortname;
            $dungeonTranslationKey = explode('.', $dungeon->name)[2];
            $continentKey          = self::CONTINENT_DUNGEONS[$dungeon->key];

            foreach ($continentNamesByLocale as $locale => $continentNames) {
                /** @var array<string, string> $continentNames */
                $namesByTranslationKey = ['name' => $continentNames[$continentKey] ?? ''];

                foreach ($dungeon->floors as $floor) {
                    /** @var Floor $floor */
                    $floorTranslationKey = sprintf('floors.%s', explode('.', $floor->name)[4]);
                    if ($floor->facade) {
                        $namesByTranslationKey[$floorTranslationKey] = $namesByTranslationKey['name'];

                        continue;
                    }

                    $namesByTranslationKey[$floorTranslationKey] = $this->findZoneName(
                        $zoneNamesBySource,
                        __($floor->name, [], 'en_US'),
                        $locale,
                    );

                    if ($namesByTranslationKey[$floorTranslationKey] === '') {
                        $this->warn(sprintf('No zone name found for %s floor "%s" in locale %s', $dungeon->key, __($floor->name, [], 'en_US'), $locale));
                    }
                }

                foreach ($namesByTranslationKey as $translationKey => $name) {
                    $path = sprintf('%s.%s.%s', $expansionKey, $dungeonTranslationKey, $translationKey);
                    // Only if we didn't have a translation yet, we add it, so manual corrections are not overwritten
                    if ($name !== '' && empty(data_get($existingTranslationsByLocale[$locale] ?? [], $path))) {
                        data_set($existingTranslationsByLocale, sprintf('%s.%s', $locale, $path), $name);
                    }
                }
            }
        }

        return $existingTranslationsByLocale;
    }

    /**
     * @param  array<int, Collection<string, array<int, string>>> $zoneNamesBySource
     * @return string                                             The zone's name in $locale, or an empty string if no source knows the zone.
     */
    private function findZoneName(array $zoneNamesBySource, string $englishZoneName, string $locale): string
    {
        $normalizedEnglishZoneName = $this->normalizeFloorName($englishZoneName);

        foreach ($zoneNamesBySource as $zoneNamesByLocale) {
            // Wowhead lists several zones per name (dev copies, phased versions); the lowest ID is the original zone
            foreach ($zoneNamesByLocale->get('en_US', []) as $zoneId => $zoneName) {
                if ($this->normalizeFloorName($zoneName) === $normalizedEnglishZoneName) {
                    return $zoneNamesByLocale->get($locale, [])[$zoneId] ?? '';
                }
            }
        }

        return '';
    }

    /**
     * Fills every dungeon and floor name that Wowhead's instance and zone lists do not carry, from the game's
     * own DB2 tables. The en_US name is matched against the game's English names. Existing names are kept, unless
     * $overwrite is set.
     *
     * @param  array<string, mixed> $translationsByLocale
     * @return array<string, mixed>
     */
    private function syncGameDataNames(WagoToolsServiceInterface $wagoToolsService, array $translationsByLocale, bool $overwrite): array
    {
        $buildsByProduct = [];
        foreach (self::GAME_DATA_NAME_SOURCES as $source) {
            $build = $buildsByProduct[$source['product']] ??= $wagoToolsService->getLatestBuild($source['product']);
            if ($build === null) {
                $this->error(sprintf('Unable to resolve a game build for product %s, skipping game data names', $source['product']));

                return $translationsByLocale;
            }
        }

        $englishTranslations  = Arr::dot(__('dungeons', [], 'en_US'));
        $gameDataIdsByKey     = $this->getGameDataIdsByKey(Dungeon::with('floors')->get());
        $englishGameDataNames = $this->readGameDataNames($wagoToolsService, $buildsByProduct, GameLocale::English);

        foreach (GameLocale::translated() as $gameLocale) {
            $locale       = $gameLocale->appLocale();
            $translations = $translationsByLocale[$locale] ?? __('dungeons', [], $locale);
            if (!is_array($translations)) {
                $translations = [];
            }

            $missingEnglishNames = [];
            foreach ($englishTranslations as $key => $englishName) {
                // Only raid sizes (difficulty.<id>) and dungeon and floor names (<expansion>.<dungeon>.name,
                // <expansion>.<dungeon>.floors.<floor>) are game data
                if (preg_match('/^(difficulty\.\d+|[^.]+\.[^.]+\.(name|floors\.[^.]+))$/', (string)$key) !== 1 || !is_string($englishName) || $englishName === '') {
                    continue;
                }

                if ($overwrite || empty(Arr::get($translations, (string)$key))) {
                    $missingEnglishNames[(string)$key] = $englishName;
                }
            }

            $localizedGameDataNames = $this->readGameDataNames($wagoToolsService, $buildsByProduct, $gameLocale);
            $gameDataNameSources    = [];
            foreach ($englishGameDataNames as $sourceKey => $englishNames) {
                $gameDataNameSources[$sourceKey] = [
                    'english'   => $englishNames,
                    'localized' => $this->isUntranslated($englishNames, $localizedGameDataNames[$sourceKey]) ? [] : $localizedGameDataNames[$sourceKey],
                ];
            }

            $resolvedNames = $this->resolveGameDataNames($missingEnglishNames, $gameDataIdsByKey, $gameDataNameSources, Arr::dot($translations));
            $changedCount  = 0;
            foreach ($resolvedNames as $key => $localizedName) {
                $existingName = Arr::get($translations, $key);
                if ($existingName !== $localizedName) {
                    if (!empty($existingName)) {
                        $this->comment(sprintf('- Replacing %s "%s" with "%s" in %s', $key, $existingName, $localizedName, $locale));
                    }

                    Arr::set($translations, $key, $localizedName);
                    $changedCount++;
                }
            }

            $this->info(sprintf('Set %d of %d looked up names for %s from game data', $changedCount, count($missingEnglishNames), $locale));
            foreach (array_diff_key($missingEnglishNames, $resolvedNames) as $key => $englishName) {
                $this->warn(sprintf('- No game data name for %s ("%s") in %s', $key, $englishName, $locale));
            }

            foreach ($translations as &$dungeons) {
                if (is_array($dungeons)) {
                    ksort($dungeons);
                }
            }
            unset($dungeons);

            $translationsByLocale[$locale] = $translations;
        }

        return $translationsByLocale;
    }

    /**
     * The DB2 rows that are known to describe each dungeon and floor name: a dungeon's instance map and zone,
     * a floor's UI map. A facade floor has no UI map of its own and shows the whole dungeon.
     *
     * @param  Collection<int, Dungeon>                      $dungeons
     * @return array<string, list<array{0: string, 1: int}>> translation key (without "dungeons.") => [source, ID]
     */
    public function getGameDataIdsByKey(Collection $dungeons): array
    {
        $result = [];
        foreach ($dungeons as $dungeon) {
            $dungeonIds = [];
            if ($dungeon->map_id >= 0) {
                $dungeonIds[] = [self::SOURCE_MAP, $dungeon->map_id];
            }

            if ($dungeon->zone_id > 0) {
                $dungeonIds[] = [self::SOURCE_AREA_TABLE, $dungeon->zone_id];
            }

            $this->addGameDataIds($result, $dungeon->name, $dungeonIds);

            foreach ($dungeon->floors as $floor) {
                $this->addGameDataIds($result, $floor->name, $floor->ui_map_id > 0 ? [
                    [self::SOURCE_UI_MAP_GROUP_MEMBER, $floor->ui_map_id],
                    [self::SOURCE_UI_MAP, $floor->ui_map_id],
                ] : $dungeonIds);
            }
        }

        return $result;
    }

    /**
     * Looks up each en_US name in the game's English names and returns the name the game uses for it in the
     * other locale. The DB2 rows known to belong to the key are tried first, then every source in order by
     * English name alone. A lookup that yields more than one different localized name is ambiguous and
     * skipped. Names found nowhere are left out, and so are keys whose existing name is already one the game
     * uses for that English name (the game often has it both with and without an article).
     *
     * @param  array<string, string>                                                                          $englishNamesByKey
     * @param  array<string, list<array{0: string, 1: int|string}>>                                           $gameDataIdsByKey
     * @param  array<string, array{english: array<int|string, string>, localized: array<int|string, string>}> $gameDataNameSources
     * @param  array<string, mixed>                                                                           $existingNamesByKey
     * @return array<string, string>
     */
    public function resolveGameDataNames(
        array $englishNamesByKey,
        array $gameDataIdsByKey,
        array $gameDataNameSources,
        array $existingNamesByKey = [],
    ): array {
        $idsByNormalizedName = [];
        foreach ($gameDataNameSources as $sourceKey => $source) {
            foreach ($source['english'] as $id => $englishName) {
                $idsByNormalizedName[$sourceKey][$this->normalizeFloorName($englishName)][] = $id;
            }
        }

        $result = [];
        foreach ($englishNamesByKey as $key => $englishName) {
            $normalizedName = $this->normalizeFloorName(self::GAME_DATA_NAME_ALIASES[$englishName] ?? $englishName);
            if ($normalizedName === '') {
                continue;
            }

            // The rows known to belong to this key, as long as the game calls them the same in English
            $candidateGroups = [[]];
            foreach ($gameDataIdsByKey[$key] ?? [] as [$sourceKey, $id]) {
                $gameDataEnglishName = $gameDataNameSources[$sourceKey]['english'][$id] ?? null;
                if ($gameDataEnglishName !== null && $this->normalizeFloorName($gameDataEnglishName) === $normalizedName) {
                    $candidateGroups[0][] = [$sourceKey, $id];
                }
            }

            foreach (array_keys($gameDataNameSources) as $sourceKey) {
                $candidateGroups[] = array_map(
                    static fn(int|string $id): array => [$sourceKey, $id],
                    $idsByNormalizedName[$sourceKey][$normalizedName] ?? [],
                );
            }

            $existingName = $existingNamesByKey[$key] ?? null;
            if (is_string($existingName) && $existingName !== '' && $this->isGameDataName($existingName, $candidateGroups, $gameDataNameSources)) {
                continue;
            }

            foreach ($candidateGroups as $candidates) {
                $localizedNames = [];
                foreach ($candidates as [$sourceKey, $id]) {
                    $localizedName = trim($gameDataNameSources[$sourceKey]['localized'][$id] ?? '');
                    if ($localizedName !== '') {
                        $localizedNames[$this->normalizeWhitespace($localizedName)] ??= $localizedName;
                    }
                }

                if (count($localizedNames) === 1) {
                    $result[$key] = (string)reset($localizedNames);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param list<list<array{0: string, 1: int|string}>>                                                    $candidateGroups
     * @param array<string, array{english: array<int|string, string>, localized: array<int|string, string>}> $gameDataNameSources
     */
    private function isGameDataName(string $name, array $candidateGroups, array $gameDataNameSources): bool
    {
        foreach ($candidateGroups as $candidates) {
            foreach ($candidates as [$sourceKey, $id]) {
                if ($this->normalizeWhitespace($gameDataNameSources[$sourceKey]['localized'][$id] ?? '') === $this->normalizeWhitespace($name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The Classic clients have no Italian (and not every table is translated in every locale); their DB2 export then
     * carries the English text in that locale's column, which must not end up in the locale's translations.
     *
     * @param array<int|string, string> $englishNames
     * @param array<int|string, string> $localizedNames
     */
    public function isUntranslated(array $englishNames, array $localizedNames): bool
    {
        $comparableCount = 0;
        $identicalCount  = 0;
        foreach ($englishNames as $id => $englishName) {
            if ($englishName === '' || !isset($localizedNames[$id]) || $localizedNames[$id] === '') {
                continue;
            }

            $comparableCount++;
            if ($localizedNames[$id] === $englishName) {
                $identicalCount++;
            }
        }

        return $comparableCount === 0 || $identicalCount / $comparableCount >= self::UNTRANSLATED_SOURCE_RATIO;
    }

    /**
     * @param array<string, list<array{0: string, 1: int}>> $gameDataIdsByKey
     * @param list<array{0: string, 1: int}>                $ids
     */
    private function addGameDataIds(array &$gameDataIdsByKey, string $translationKey, array $ids): void
    {
        if (!str_starts_with($translationKey, 'dungeons.')) {
            return;
        }

        $key = substr($translationKey, strlen('dungeons.'));
        foreach ($ids as $id) {
            $gameDataIdsByKey[$key][] = $id;
        }
    }

    /**
     * @param  array<string, string|null>               $buildsByProduct
     * @return array<string, array<int|string, string>> source => ID => name
     */
    private function readGameDataNames(WagoToolsServiceInterface $wagoToolsService, array $buildsByProduct, GameLocale $gameLocale): array
    {
        $result = [];
        foreach (self::GAME_DATA_NAME_SOURCES as $sourceKey => $source) {
            $names = [];
            foreach ($wagoToolsService->readTable($source['table'], (string)$buildsByProduct[$source['product']], $gameLocale) as $row) {
                $names[$row[$source['idColumn']] ?? ''] = $row[$source['nameColumn']] ?? '';
            }

            $result[$sourceKey] = $names;
        }

        return $result;
    }

    /**
     * The game writes the same name with a regular and a non-breaking space ("20 joueurs").
     */
    private function normalizeWhitespace(string $name): string
    {
        return trim((string)preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * Wowhead and the en_US floor names differ in punctuation and case only ("Vereesa's Repose - Upper" vs
     * "Vereesa's Repose Upper"), so floors are matched on letters and digits alone.
     */
    public function normalizeFloorName(string $floorName): string
    {
        return mb_strtolower((string)preg_replace('/[^\p{L}\p{N}]/u', '', $floorName));
    }

    /**
     * @param array<string, mixed> $updatedTranslations
     */
    private function saveTranslationsToDisk(array $updatedTranslations): void
    {
        foreach ($updatedTranslations as $locale => $newTranslations) {
            if ($this->hasAILanguage($locale)) {
                $aiLocale = sprintf('%s_ai', $locale);
                $this->exportTranslations(
                    $aiLocale,
                    'dungeons.php',
                    $this->mergeIntoAiTranslations(__('dungeons', [], 'en_US'), __('dungeons', [], $aiLocale), $newTranslations),
                );
            }
            $this->exportTranslations($locale, 'dungeons.php', $newTranslations);
        }
    }

    /**
     * An _ai locale holds every en_US key, empty until a name is known, and keeps names its base locale lacks. So the
     * en_US keys and the existing _ai file come first, and only non-empty base names are laid over them.
     *
     * @param  array<string, mixed>|string $englishTranslations
     * @param  array<string, mixed>|string $existingAiTranslations
     * @param  array<string, mixed>        $newTranslations
     * @return array<string, mixed>
     */
    public function mergeIntoAiTranslations(
        array|string $englishTranslations,
        array|string $existingAiTranslations,
        array        $newTranslations,
    ): array {
        $mergedTranslations = array_replace_recursive(
            $this->withEmptyNames(is_array($englishTranslations) ? $englishTranslations : []),
            is_array($existingAiTranslations) ? $existingAiTranslations : [],
            $this->withoutEmptyNames($newTranslations),
        );
        foreach ($mergedTranslations as &$dungeons) {
            if (is_array($dungeons)) {
                ksort($dungeons);
            }
        }

        return $mergedTranslations;
    }

    /**
     * @param  array<string, mixed> $translations
     * @return array<string, mixed>
     */
    private function withEmptyNames(array $translations): array
    {
        $result = [];
        foreach ($translations as $key => $value) {
            $result[$key] = is_array($value) ? $this->withEmptyNames($value) : '';
        }

        return $result;
    }

    /**
     * @param  array<string, mixed> $translations
     * @return array<string, mixed>
     */
    private function withoutEmptyNames(array $translations): array
    {
        $result = [];
        foreach ($translations as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->withoutEmptyNames($value);
            } elseif ($value !== null && $value !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
