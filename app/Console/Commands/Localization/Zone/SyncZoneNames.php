<?php

namespace App\Console\Commands\Localization\Zone;

use App\Console\Commands\Localization\Traits\ExportsTranslations;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Service\Wowhead\WowheadTranslationServiceInterface;
use Exception;
use Illuminate\Console\Command;

class SyncZoneNames extends Command
{
    use ExportsTranslations;

    const EXCLUDE_DUNGEONS = [
        Dungeon::DUNGEON_SCARLET_MONASTERY_ARMORY,
        Dungeon::DUNGEON_SCARLET_MONASTERY_CATHEDRAL,
        Dungeon::DUNGEON_SCARLET_MONASTERY_GRAVEYARD,
        Dungeon::DUNGEON_SCARLET_MONASTERY_LIBRARY,

        Dungeon::DUNGEON_DIRE_MAUL_EAST,
        Dungeon::DUNGEON_DIRE_MAUL_NORTH,
        Dungeon::DUNGEON_DIRE_MAUL_WEST,

        Dungeon::DUNGEON_DAWN_OF_THE_INFINITE_GALAKRONDS_FALL,
        Dungeon::DUNGEON_DAWN_OF_THE_INFINITE_MUROZONDS_RISE,

        Dungeon::DUNGEON_TAZAVESH_SO_LEAHS_GAMBIT,
        Dungeon::DUNGEON_TAZAVESH_STREETS_OF_WONDER,
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

        $this->saveTranslationsToDisk($updatedTranslations);
    }

    private function syncDungeonNames(WowheadTranslationServiceInterface $wowheadTranslationService): array
    {
        $dungeonNamesByLocale = $wowheadTranslationService->getDungeonNames();

        $dungeonsById = Dungeon::all()
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
                    continue;
                }

                // Ensure expansion array is set
                if (!isset($updatedTranslations[$locale][$dungeon->expansion->shortname])) {
                    $updatedTranslations[$locale][$dungeon->expansion->shortname] = [];
                }

                $updatedTranslations[$locale][$dungeon->expansion->shortname][explode('.', $dungeon->name)[2]]['name'] = $dungeonName;
            }

            $updatedTranslations[$locale] = array_replace_recursive($existingTranslations, $updatedTranslations[$locale]);
        }

        return $updatedTranslations;
    }

    private function syncFloorNames(WowheadTranslationServiceInterface $wowheadTranslationService, array $existingTranslationsByLocale): array
    {
        $floorNamesByLocale = $wowheadTranslationService->getFloorNames();
        $dungeonsByZoneId = Dungeon::all()
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
            /** @var Dungeon $dungeon */
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
                    if ($floorName === __($floor->name, [], 'en_US')) {
                        // We found the KSG floor for this name, so we can store where to find it in $dungeonZoneIdIndexReference
                        $dungeonZoneIdIndexReference[$floor->id] = [
                            'index'          => $floorIndex,
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
            /** @var Floor $facadeFloor */
            $facadeFloor = $dungeon->floors->firstWhere('facade', true);
            if ($facadeFloor !== null) {
                $dungeonZoneIdIndexReference[$facadeFloor->id] = [
                    // Facade floor is always the last floor (one will be added shortly so this will match up)
                    'index'          => count($floorNames),
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
                $floor = $dungeon->floors->first();
                $dungeonZoneIdIndexReference[$floor->id] = [
                    // 0 based if the dungeon was not found in the Wowhead data
                    'index'          => 0,
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
            /** @var array $floorNamesForLocale */

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
                    $floorNamesForLocale[$zoneId][] = $existingTranslationsByLocale[$locale][$dungeon->expansion->shortname][$dungeonTranslationKey]['name'];

                    $updatedTranslations[$dungeon->expansion->shortname][$dungeonTranslationKey] = [
                        'floors' => [],
                    ];

                    foreach ($floorData as $floorId => $data) {
                        if (!isset($floorNamesForLocale[$zoneId][$data['index']])) {
                            $this->warn(sprintf('No floor name found for zone ID %d and index %d in locale %s', $zoneId, $data['index'], $locale));
                            continue;
                        }

                        $updatedTranslations[$dungeon->expansion->shortname][$dungeonTranslationKey]['floors'][$data['translationKey']] = $floorNamesForLocale[$zoneId][$data['index']];
                    }

//                    if ($dungeon->key === Dungeon::DUNGEON_OPERATION_FLOODGATE) {
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

    private function saveTranslationsToDisk(array $updatedTranslations): void
    {
        foreach ($updatedTranslations as $locale => $newTranslations) {
            $this->exportTranslations($locale, 'dungeons.php', $newTranslations);
        }
    }
}
