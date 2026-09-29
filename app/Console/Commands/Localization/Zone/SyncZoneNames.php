<?php

namespace App\Console\Commands\Localization\Zone;

use App\Console\Commands\Localization\BaseSyncCommand;
use App\Console\Commands\Localization\Traits\ExportsTranslations;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Service\Wowhead\WowheadTranslationServiceInterface;
use Exception;
use Illuminate\Support\Collection;

class SyncZoneNames extends BaseSyncCommand
{
    use ExportsTranslations;

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
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:synczonenames';

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
    public function handle(WowheadTranslationServiceInterface $wowheadTranslationService): void
    {
        $updatedTranslations = $this->syncDungeonNames($wowheadTranslationService);

        $updatedTranslations = $this->syncFloorNames($wowheadTranslationService, $updatedTranslations);

        $updatedTranslations = $this->syncContinentNames($wowheadTranslationService, $updatedTranslations);

        $this->saveTranslationsToDisk($updatedTranslations);
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
        // Zone ID 0 is shared by every dungeon without an instance zone (the continents among them), so keying on it
        // would keep only one of them
        $dungeonsByZoneId = Dungeon::with(['expansion', 'floors'])
            ->where('zone_id', '>', 0)
            ->get()
            ->keyBy('zone_id');

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
