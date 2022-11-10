<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Jobs\RefreshEnemyForces;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTString;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\NpcClassification;
use App\Models\NpcType;
use App\Service\Cache\CacheServiceInterface;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\MDT\MDTMappingExportServiceInterface;
use App\Traits\SavesArrayToJsonFile;
use Artisan;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Session;
use Throwable;

class AdminToolsController extends Controller
{
    use ChangesMapping;
    use SavesArrayToJsonFile;

    /**
     * @return Factory|View
     */
    public function index()
    {
        return view('admin.tools.list');
    }

    /**
     * @return Application|Factory|View
     */
    public function npcimport()
    {
        return view('admin.tools.npcimport.import');
    }

    /**
     * @param Request $request
     * @return void
     */
    public function npcimportsubmit(Request $request)
    {
        $importString = $request->get('import_string');

        // Correct the string since wowhead sucks
        $importString = str_replace('[Listview.extraCols.popularity]', '["Listview.extraCols.popularity"]', $importString);

        $decoded = json_decode($importString, true);

        $log = [];

        // Wowhead type => keystone.guru type
        $npcTypeMapping = [
            15 => NpcType::ABERRATION,
            1  => NpcType::BEAST,
            8  => NpcType::CRITTER,
            3  => NpcType::DEMON,
            2  => NpcType::DRAGONKIN,
            4  => NpcType::ELEMENTAL,
            5  => NpcType::GIANT,
            7  => NpcType::HUMANOID,
            9  => NpcType::MECHANICAL,
            6  => NpcType::UNDEAD,
            10 => NpcType::UNCATEGORIZED,
            // 12 => NpcType::BATTLE_PET,
        ];

        $aggressivenessMapping = [
            -1 => 'aggressive',
            0  => 'neutral',
            1  => 'friendly',
        ];

        try {
            foreach ($decoded['data'] as $npcData) {
                $npcCandidate = Npc::findOrNew($npcData['id']);

                $beforeModel = clone $npcCandidate;

                /** @var Dungeon $dungeon */
                $dungeon = Dungeon::where('zone_id', $npcData['location'][0])->first();
                if ($dungeon === null) {
                    $log[] = sprintf('*** Unable to find dungeon with zone_id %s; npc %s (%s) NOT added; needs manual work', $npcData['location'][0], $npcData['id'], $npcData['name']);
                    continue;
                }

                if (!isset($npcTypeMapping[$npcData['type']])) {
                    $log[] = sprintf('*** Unable to find npc type %s; npc %s (%s) NOT added; needs manual work', $npcData['type'], $npcData['id'], $npcData['name']);
                    continue;
                }

                $npcCandidate->id                = $npcData['id'];
                $npcCandidate->classification_id = ($npcData['classification'] ?? 0) + ($npcData['boss'] ?? 0) + 1;
                // Bosses
                if ($npcCandidate->classification_id >= NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                    $npcCandidate->enemy_forces = 0;
                }
                $npcCandidate->npc_type_id = $npcTypeMapping[$npcData['type']];
                // 8 since we start the expansion with 8 dungeons usually
                $npcCandidate->dungeon_id = count($npcData['location']) >= 8 ? -1 : $dungeon->id;
                $npcCandidate->name       = $npcData['name'];
                // Do not overwrite health if it was set already
                if ($npcCandidate->base_health <= 0) {
                    $npcCandidate->base_health = 12345;
                }
                $npcCandidate->aggressiveness = isset($npcData['react']) && is_array($npcData['react']) ? $aggressivenessMapping[$npcData['react'][0] ?? -1] : 'aggressive';

                $existed = $npcCandidate->exists;
                if ($npcCandidate->save()) {
                    if ($existed) {
                        $log[] = sprintf('Updated NPC %s (%s) in %s', $npcData['id'], $npcData['name'], __($dungeon->name));
                    } else {
                        $log[] = sprintf('Inserted NPC %s (%s) in %s', $npcData['id'], $npcData['name'], __($dungeon->name));
                    }
                } else {
                    $log[] = sprintf('*** Error saving NPC %s (%s) in %s', $npcData['id'], $npcData['name'], __($dungeon->name));
                }

                // Changed the mapping; so make sure we synchronize it
                $this->mappingChanged($beforeModel, $npcCandidate);
            }
        } catch (Exception $ex) {
            dump($ex);
        } finally {
            dump($log);
        }
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function dungeonroute()
    {
        return view('admin.tools.dungeonroute.view');
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function dungeonroutesubmit(Request $request)
    {
        $dungeonRoute = DungeonRoute::with([
            'faction', 'specializations', 'classes', 'races', 'affixes',
            'brushlines', 'paths', 'author', 'killzones', 'pridefulenemies', 'publishedstate',
            'ratings', 'favorites', 'enemyraidmarkers', 'mapicons', 'mdtImport', 'team',
        ])->where('public_key', $request->get('public_key'))->firstOrFail();

        return view('admin.tools.dungeonroute.viewcontents', [
            'dungeonroute' => $dungeonRoute,
        ]);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function enemyforcesimport()
    {
        return view('admin.tools.enemyforces.import');
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function enemyforcesimportsubmit(Request $request)
    {
        $json = json_decode($request->get('import_string'), true);

        $results = [];
        foreach ($json['Npcs'] as $jsonNpc) {
            $npc = Npc::where('id', $jsonNpc['Id'])->first();

            if ($npc !== null) {
                $keyMapping = [
                    'MythicHealth' => 'base_health',
                    'Amount'       => 'enemy_forces',
                ];

                $toUpdate = [];
                foreach ($jsonNpc as $key => $value) {
                    if ($key !== 'Id' && $value >= 0 && isset($keyMapping[$key])) {
                        $toUpdate[$keyMapping[$key]] = $value;
                    }
                }
                $npc->update($toUpdate);

                $results[] = sprintf('Changed npc %d fields: %s', $jsonNpc['Id'], json_encode($toUpdate));
            } else {
                $results[] = sprintf('Unable to find npc %d', $jsonNpc['Id']);
            }
        }

        dd($results);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function enemyforcesrecalculate()
    {
        return view('admin.tools.enemyforces.recalculate');
    }

    /**
     * @param Request $request
     * @return void
     */
    public function enemyforcesrecalculatesubmit(Request $request)
    {
        $dungeonId = (int)$request->get('dungeon_id');


        $builder = DungeonRoute::without(['faction', 'specializations', 'classes', 'races', 'affixes'])
            ->select('id')
            ->when($dungeonId !== -1, function (Builder $builder) use ($dungeonId) {
                return $builder->where('dungeon_id', $dungeonId);
            });

        // All dungeons
        $count = 0;
        foreach ($builder->get() as $dungeonRoute) {
            RefreshEnemyForces::dispatch($dungeonRoute->id);
            $count++;
        }

        dd(sprintf('Dispatched %d jobs', $count));
    }

    /**
     * @return Factory|
     */
    public function mdtview()
    {
        return view('admin.tools.mdt.string');
    }

    /**
     * @param Request $request
     * @param MDTImportStringServiceInterface $mdtImportStringService
     * @return JsonResponse
     */
    public function mdtviewsubmit(Request $request, MDTImportStringServiceInterface $mdtImportStringService)
    {
        return response()->json(
            $mdtImportStringService
                ->setEncodedString($request->get('import_string'))
                ->getDecoded()
        );
    }

    /**
     * @return Factory|
     */
    public function mdtviewasdungeonroute()
    {
        return view('admin.tools.mdt.string', ['asDungeonroute' => true]);
    }

    /**
     * @param Request $request
     * @param MDTImportStringServiceInterface $mdtImportStringService
     * @return never|void
     * @throws Throwable
     */
    public function mdtviewasdungeonroutesubmit(Request $request, MDTImportStringServiceInterface $mdtImportStringService)
    {
        try {
            $dungeonRoute = $mdtImportStringService
                ->setEncodedString($request->get('import_string'))
                ->getDungeonRoute(new Collection(), false, false);
            $dungeonRoute->makeVisible(['affixes', 'killzones']);

            dd($dungeonRoute);
        } catch (InvalidMDTString $ex) {
            return abort(400, __('controller.admintools.error.mdt_string_format_not_recognized'));
        } catch (Exception $ex) {

            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('controller.admintools.error.invalid_mdt_string_exception'), $ex->getMessage());
            } else {
                $message = __('controller.admintools.error.invalid_mdt_string');
            }
            return abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, __('controller.admintools.error.mdt_importer_not_configured'));
            }

            throw $error;
        }
    }

    /**
     * @return Factory|
     */
    public function mdtviewasstring()
    {
        return view('admin.tools.mdt.dungeonroute');
    }

    /**
     * @param Request $request
     * @param MDTImportStringServiceInterface $mdtImportStringService
     * @param MDTExportStringServiceInterface $mdtExportStringService
     * @return never|void
     * @throws Throwable
     */
    public function mdtviewasstringsubmit(Request $request, MDTImportStringServiceInterface $mdtImportStringService, MDTExportStringServiceInterface $mdtExportStringService)
    {
        $dungeonRoute = DungeonRoute::where('public_key', $request->get('public_key'))->firstOrFail();

        try {
            $warnings = new Collection();

            $exportString = $mdtExportStringService
                ->setDungeonRoute($dungeonRoute)
                ->getEncodedString($warnings);

            $stringContents = $mdtImportStringService
                ->setEncodedString($exportString)
                ->getDecoded();

            dd($exportString, $stringContents);
        } catch (Exception $ex) {

            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('controller.admintools.error.invalid_mdt_string_exception'), $ex->getMessage());
            } else {
                $message = __('controller.admintools.error.invalid_mdt_string');
            }
            return abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, __('controller.admintools.error.mdt_importer_not_configured'));
            }

            throw $error;
        }
    }

    /**
     * @return Factory|
     */
    public function mdtdungeonmappinghash()
    {
        return view('admin.tools.mdt.dungeonmappinghash');
    }

    /**
     * @param Request $request
     * @param MDTMappingExportServiceInterface $mdtMappingService
     * @return void
     * @throws Throwable
     */
    public function mdtdungeonmappinghashsubmit(Request $request, MDTMappingExportServiceInterface $mdtMappingService)
    {
        $dungeon = Dungeon::findOrFail($request->get('dungeon_id'));

        dd($mdtMappingService->getMDTMappingHash($dungeon->key));
    }

    /**
     * @return Factory|
     */
    public function dungeonmappingversiontomdtmapping()
    {
        return view('admin.tools.mdt.dungeonmappingversiontomdtmapping', [
            'mappingVersionsSelect' => MappingVersion::orderBy('dungeon_id')->get()->mapWithKeys(function (MappingVersion $mappingVersion) {
                return [$mappingVersion->id => sprintf('%s - Version %d (%d)', __($mappingVersion->dungeon->name), $mappingVersion->version, $mappingVersion->id)];
            }),
        ]);
    }

    /**
     * @param Request $request
     * @param MDTMappingExportServiceInterface $mdtMappingService
     * @return void
     * @throws Throwable
     */
    public function dungeonmappingversiontomdtmappingsubmit(Request $request, MDTMappingExportServiceInterface $mdtMappingService)
    {
        $mappingVersion = MappingVersion::findOrFail($request->get('mapping_version_id'));

        dd($mdtMappingService->getMDTMapping($mappingVersion));
    }


    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function importingamecoordinates()
    {
        return view('admin.tools.wowtools.importingamecoordinates');
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     * @throws Exception
     */
    public function importingamecoordinatessubmit(Request $request)
    {
        // Parse all Map CSV data and convert them to a workable format
        $mapCsv                   = $request->get('map_csv');
        $mapCsvParsed             = str_getcsv_assoc($mapCsv);
        $mapCsvHeaders            = array_shift($mapCsvParsed);
        $mapCsvHeaderIndexMapName = array_search('MapName_lang', $mapCsvHeaders);
        $mapCsvHeaderIndexMapId   = array_search('ID', $mapCsvHeaders);

        // Parse all Map Group Member CSV data and convert them to a workable format
        $mapGroupMemberCsv                    = $request->get('ui_map_group_member_csv');
        $mapGroupMemberCsvParsed              = str_getcsv_assoc($mapGroupMemberCsv);
        $mapGroupMemberCsvHeaders             = array_shift($mapGroupMemberCsvParsed);
        $mapGroupMemberCsvHeaderIndexNameLang = array_search('Name_lang', $mapGroupMemberCsvHeaders);
        $mapGroupMemberCsvHeaderIndexUiMapId  = array_search('UiMapID', $mapGroupMemberCsvHeaders);

        // Parse all UI Map Assignment CSV data and convert them to a workable format
        $uiMapAssignmentCsv                      = $request->get('ui_map_assignment_csv');
        $uiMapAssignmentCsvParsed                = str_getcsv_assoc($uiMapAssignmentCsv);
        $uiMapAssignmentCsvHeaders               = array_shift($uiMapAssignmentCsvParsed);
        $uiMapAssignmentCsvHeaderIndexMapId      = array_search('MapID', $uiMapAssignmentCsvHeaders);
        $uiMapAssignmentCsvHeaderIndexUiMapId    = array_search('UiMapID', $uiMapAssignmentCsvHeaders);
        $uiMapAssignmentCsvHeaderIndexOrderIndex = array_search('OrderIndex', $uiMapAssignmentCsvHeaders);
        $uiMapAssignmentCsvHeaderIndexMinX       = array_search('Region[0]', $uiMapAssignmentCsvHeaders);
        $uiMapAssignmentCsvHeaderIndexMinY       = array_search('Region[1]', $uiMapAssignmentCsvHeaders);
        $uiMapAssignmentCsvHeaderIndexMaxX       = array_search('Region[3]', $uiMapAssignmentCsvHeaders);
        $uiMapAssignmentCsvHeaderIndexMaxY       = array_search('Region[4]', $uiMapAssignmentCsvHeaders);

        /** @var Collection|Dungeon[] $allDungeons */
//        $allDungeons = Dungeon::where('key', Dungeon::DUNGEON_IRON_DOCKS)->get()->keyBy('id');
        $allDungeons = Dungeon::all()->keyBy('id');

        $changedDungeons = collect();

        // Go over the Map CSV and parse the map_id - it's the only thing we're interested in
        foreach ($mapCsvParsed as $row) {
            foreach ($allDungeons as $dungeon) {
                // The map names don't always match up (the combined dungeons such as Karazhan seem problematic, have to do this by hand)
                $mapId = (int)$row[$mapCsvHeaderIndexMapId];
                if (trim($row[$mapCsvHeaderIndexMapName]) === __($dungeon->name)) {
                    if ($dungeon->map_id !== $mapId) {
                        $beforeModel = clone $dungeon;

                        $dungeon->update(['map_id' => $mapId]);

                        // Ensure that the mapping site sees this as a change
                        $this->mappingChanged($beforeModel, $dungeon);
                    }

                    // We just want to know which dungeons did NOT have a map_id set, but if we found the dungeon we're okay
                    $changedDungeons->put($dungeon->id, $dungeon);
                    break;
                }
            }
        }

        // Keep track of the unchanged dungeons so that we can notify them as a warning at the end of the call
        $unchangedDungeons = $allDungeons->diffKeys($changedDungeons);

        // Go over the UI Map Assignments and find the ones we're interested in
        foreach ($uiMapAssignmentCsvParsed as $row) {
            foreach ($allDungeons as $dungeon) {
                if ((int)$row[$uiMapAssignmentCsvHeaderIndexMapId] === $dungeon->map_id &&
                    (int)$row[$uiMapAssignmentCsvHeaderIndexOrderIndex] === 0) {
                    // Now that we know the UI map ID - cross-reference it with the map group to get the correct floor
                    $uiMapId = (int)$row[$uiMapAssignmentCsvHeaderIndexUiMapId];

                    // Try to find the map group member (floor definition) - NOTE: sometimes this doesn't exist,
                    // and you'll have to manually verify it without!
                    $foundMapGroupMember = false;
                    foreach ($mapGroupMemberCsvParsed as $mapGroupMemberRow) {
                        if ((int)$mapGroupMemberRow[$mapGroupMemberCsvHeaderIndexUiMapId] === $uiMapId) {
                            // We found the group member - now find which floor it was for
                            $foundFloor = false;
                            foreach ($dungeon->floors as $floor) {
                                if (trim($mapGroupMemberRow[$mapGroupMemberCsvHeaderIndexNameLang]) === __($floor->name)) {
                                    $beforeModel = clone $floor;

                                    $floor->update([
                                        'ingame_min_x' => $row[$uiMapAssignmentCsvHeaderIndexMinX],
                                        'ingame_min_y' => $row[$uiMapAssignmentCsvHeaderIndexMinY],
                                        'ingame_max_x' => $row[$uiMapAssignmentCsvHeaderIndexMaxX],
                                        'ingame_max_y' => $row[$uiMapAssignmentCsvHeaderIndexMaxY],
                                    ]);

                                    $this->mappingChanged($beforeModel, $floor);
                                    $foundFloor = true;
                                    break;
                                }
                            }

                            if (!$foundFloor) {
                                dump(sprintf('Unable to find floor with id %d and name %s (typo in name?)',
                                    (int)$mapGroupMemberRow[$mapGroupMemberCsvHeaderIndexUiMapId],
                                    $mapGroupMemberRow[$mapGroupMemberCsvHeaderIndexNameLang]
                                ));
                            }

                            $foundMapGroupMember = true;
                            break;
                        }
                    }

                    if (!$foundMapGroupMember) {
                        dump(sprintf('Unable to find map group member with ui map id %d',
                            (int)$row[$uiMapAssignmentCsvHeaderIndexUiMapId]
                        ), $row);
                    }
                }
            }
        }


        dd($changedDungeons, $unchangedDungeons->pluck('name')->toArray());
    }


    /**
     * @return Factory|
     * @throws Exception
     */
    public function mdtdiff()
    {
        $warnings = new Collection();
        $npcs     = Npc::with(['enemies', 'type'])->get();

        // For each dungeon
        foreach (Dungeon::all() as $dungeon) {
            /** @var Dungeon $dungeon */
            $mdtNpcs = (new MDTDungeon($dungeon->key))->getMDTNPCs();

            // For each NPC that is found in the MDT Dungeon
            foreach ($mdtNpcs as $mdtNpc) {
                // Ignore mobs we should ignore
                if (!$mdtNpc->isValid() || $mdtNpc->isAwakened()) {
                    continue;
                }

                // Find our own NPC
                /** @var Npc $npc */
                $npc = $npcs->where('id', $mdtNpc->getId())->first();

                // Not found..
                if ($npc === null) {
                    $warnings->push(
                        new ImportWarning('missing_npc',
                            sprintf(__('controller.admintools.error.mdt_unable_to_find_npc_for_id'), $mdtNpc->getId()),
                            ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc]
                        )
                    );
                } // Found, compare
                else {

                    // Match health
                    if ($npc->base_health !== $mdtNpc->getHealth()) {
                        $warnings->push(
                            new ImportWarning('mismatched_health',
                                sprintf(__('controller.admintools.error.mdt_mismatched_health'), $mdtNpc->getId(), $mdtNpc->getHealth(), $npc->base_health),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->base_health, 'new' => $mdtNpc->getHealth()]
                            )
                        );
                    }

                    // Match enemy forces
                    if ($npc->enemy_forces !== $mdtNpc->getCount()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces'), $mdtNpc->getId(), $mdtNpc->getCount(), $npc->enemy_forces),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->enemy_forces, 'new' => $mdtNpc->getCount()]
                            )
                        );
                    }

                    // Match enemy forces teeming
                    if ($npc->enemy_forces_teeming !== $mdtNpc->getCountTeeming()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces_teeming',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces_teeming'), $mdtNpc->getId(), $mdtNpc->getCountTeeming(), $npc->enemy_forces_teeming),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->enemy_forces_teeming, 'new' => $mdtNpc->getCountTeeming()]
                            )
                        );
                    }

                    // Match clone count, should be equal
                    if ($npc->enemies->count() !== count($mdtNpc->getClones())) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_count',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_count'),
                                    $mdtNpc->getId(), count($mdtNpc->getClones()), $npc->enemies === null ? 0 : $npc->enemies->count()),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc]
                            )
                        );
                    }

                    // Match npc type, should be equal
                    if ($npc->type->type !== $mdtNpc->getCreatureType()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_type',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_type'),
                                    $mdtNpc->getId(), $mdtNpc->getCreatureType(), $npc->type->type),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->type->type, 'new' => $mdtNpc->getCreatureType()]
                            )
                        );
                    }
                }
            }
        }

        return view('admin.tools.mdt.diff', ['warnings' => $warnings]);
    }

    /**
     * @param Request $request
     * @param CacheServiceInterface $cacheService
     * @return RedirectResponse
     */
    public function dropCache(Request $request, CacheServiceInterface $cacheService)
    {
        ini_set('max_execution_time', -1);

        $cacheService->dropCaches();

        Artisan::call('modelCache:clear');

        Artisan::call('keystoneguru:view', ['operation' => 'cache']);

        Session::flash('status', __('controller.admintools.flash.caches_dropped_successfully'));

        return redirect()->route('admin.tools');
    }

    /**
     * @param Request $request
     * @return void
     */
    public function mappingForceSync(Request $request)
    {
        Artisan::call('mapping:sync', ['--force' => true]);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function applychange(Request $request)
    {
        $category = $request->get('category');
        $npcId    = $request->get('npc_id');
        $value    = $request->get('value');

        /** @var Npc $npc */
        $npc = Npc::find($npcId);

        switch ($category) {
            case 'mismatched_health':
                $npc->base_health = $value;
                $npc->save();
                break;
            case 'mismatched_enemy_forces':
                $npc->enemy_forces = $value;
                $npc->save();
                break;
            case 'mismatched_enemy_forces_teeming':
                $npc->enemy_forces_teeming = $value;
                $npc->save();
                break;
            case 'mismatched_enemy_type':
                $npcType          = NpcType::where('type', $value)->first();
                $npc->npc_type_id = $npcType->id;
                $npc->save();
                break;
            default:
                abort(500, __('controller.admintools.error.mdt_invalid_category'));
        }

        // Whatever
        return [];
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function exportreleases(Request $request)
    {
        Artisan::call('release:save');

        Session::flash('status', __('controller.admintools.flash.releases_exported'));

        return view('admin.tools.list');
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    public function exportdungeondata(Request $request)
    {
        Artisan::call('mapping:save');

        return view('admin.tools.datadump.viewexporteddungeondata');
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function exceptionselect(Request $request)
    {
        return view('admin.tools.exception.select');
    }

    /**
     * @param Request $request
     * @throws TokenMismatchException
     * @throws Exception
     */
    public function exceptionselectsubmit(Request $request)
    {
        switch ($request->get('exception')) {
            case 'TokenMismatchException':
                throw new TokenMismatchException(__('controller.admintools.flash.exception.token_mismatch'));
            case 'InternalServerError':
                throw new Exception(__('controller.admintools.flash.exception.internal_server_error'));
        }
    }
}
