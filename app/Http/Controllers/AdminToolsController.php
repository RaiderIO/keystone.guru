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
use App\Models\Npc\NpcEnemyForces;
use App\Models\NpcClassification;
use App\Models\NpcType;
use App\Service\Cache\CacheServiceInterface;
use App\Service\CombatLog\CombatLogDungeonRouteServiceInterface;
use App\Service\DungeonRoute\ThumbnailService;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\MDT\MDTMappingExportServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
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
     * @param CombatLogDungeonRouteServiceInterface $combatLogDungeonRouteService
     * @return RedirectResponse
     */
    public function combatlog(CombatLogDungeonRouteServiceInterface $combatLogDungeonRouteService): RedirectResponse
    {
        $dungeonRoute = $combatLogDungeonRouteService->convertCombatLogToDungeonRoute(
            base_path(
                'tests/Unit/App/Service/CombatLog/Fixtures/18_neltharions_lair/combat.log'
            )
        );

        return redirect()->route('dungeonroute.view', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ]);
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
                    $npcCandidate->dangerous = true;
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
                        // Now create new enemy forces. Default to 0
                        $npcCandidate->createNpcEnemyForcesForExistingMappingVersions();

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
            'brushlines', 'paths', 'author', 'killzones', 'pridefulEnemies', 'publishedstate',
            'ratings', 'favorites', 'enemyraidmarkers', 'mapicons', 'mdtImport', 'team',
        ])->where('public_key', $request->get('public_key'))->firstOrFail();

        return view('admin.tools.dungeonroute.viewcontents', [
            'dungeonroute' => $dungeonRoute,
        ]);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function dungeonrouteMappingVersions()
    {
        $mappingVersionUsage = MappingVersion::orderBy('dungeon_id')
            ->get()
            ->mapWithKeys(function (MappingVersion $mappingVersion) {
                return [$mappingVersion->getPrettyName() => $mappingVersion->dungeonRoutes()->count()];
            })
            ->groupBy(function (int $count, string $key) {
                return $count === 0;
            }, true);

        return view('admin.tools.dungeonroute.mappingversions', [
            'mappingVersionUsage' => collect([
                'unused' => $mappingVersionUsage[1],
                'used'   => $mappingVersionUsage[0],
            ]),
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

        $count = 0;
        foreach ($builder->get() as $dungeonRoute) {
            RefreshEnemyForces::dispatch($dungeonRoute->id);
            $count++;
        }

        dd(sprintf('Dispatched %d jobs', $count));
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function thumbnailsregenerate()
    {
        return view('admin.tools.thumbnails.regenerate');
    }

    /**
     * @param Request $request
     * @param ThumbnailService $thumbnailService
     * @return void
     */
    public function thumbnailsregeneratesubmit(Request $request, ThumbnailService $thumbnailService)
    {
        set_time_limit(3600);

        $dungeonId = (int)$request->get('dungeon_id');

        $builder = DungeonRoute::without(['faction', 'specializations', 'classes', 'races', 'affixes'])
            ->with('dungeon')
            ->when($dungeonId !== -1, function (Builder $builder) use ($dungeonId) {
                return $builder->where('dungeon_id', $dungeonId);
            });

        $count         = 0;
        $dungeonRoutes = $builder->get();
        foreach ($dungeonRoutes as $dungeonRoute) {
            $thumbnailService->queueThumbnailRefresh($dungeonRoute);
            $count++;
        }

        dd(sprintf('Dispatched %d jobs for %d routes', $count, $dungeonRoutes->count()));
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
     * @param MDTMappingImportServiceInterface $mdtMappingService
     * @return void
     * @throws Throwable
     */
    public function mdtdungeonmappinghashsubmit(Request $request, MDTMappingImportServiceInterface $mdtMappingService)
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
            'mappingVersionsSelect' => MappingVersion::orderBy('dungeon_id')
                ->get()
                ->groupBy('dungeon_id')
                ->mapWithKeys(function (Collection $mappingVersionByDungeon, int $id) {
                    $dungeon = Dungeon::findOrFail($id);
                    return [
                        __($dungeon->name) =>
                            $mappingVersionByDungeon->mapWithKeys(function (MappingVersion $mappingVersion) use ($dungeon) {
                                return [
                                    $mappingVersion->id => $mappingVersion->getPrettyName(),
                                ];
                            }),
                    ];
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

        echo $mdtMappingService->getMDTMappingAsLuaString($mappingVersion);
        dd();
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
        // Parse all Map TABLE data and convert them to a workable format
        $mapTable                   = $request->get('map_table_xhr_response');
        $mapTableParsed             = json_decode($mapTable, true)['data'];
        $mapTableHeaders            = [
            'ID', 'Directory', 'MapName_lang', 'MapDescription0_lang', 'MapDescription1_lang', 'PvpShortDescription_lang',
            'Corpse[0]', 'Corpse[1]', 'MapType', 'InstanceType', 'ExpansionID', 'AreaTableID', 'LoadingScreenID',
            'TimeOfDayOverride', 'ParentMapId', 'CosmeticParentMapID', 'TimeOffset', 'MinimapIconScale', 'CorpseMapID',
            'MaxPlayers', 'WindSettingsID', 'ZmpFileDataID', 'WdtFileDataID', 'NavigationMaxDistance', 'Flags[0]',
            'Flags[1]', 'Flags[2]',
        ];
        $mapTableHeaderIndexMapName = array_search('MapName_lang', $mapTableHeaders);
        $mapTableHeaderIndexMapId   = array_search('ID', $mapTableHeaders);

        // Parse all Map Group Member TABLE data and convert them to a workable format
        $mapGroupMemberTable                    = $request->get('ui_map_group_member_table_xhr_response');
        $mapGroupMemberTableParsed              = json_decode($mapGroupMemberTable, true)['data'];
        $mapGroupMemberTableHeaders             = [
            'ID', 'Name_lang', 'UiMapGroupID', 'UiMapID', 'FloorIndex', 'RelativeHeightIndex',
        ];
        $mapGroupMemberTableHeaderIndexNameLang = array_search('Name_lang', $mapGroupMemberTableHeaders);
        $mapGroupMemberTableHeaderIndexUiMapId  = array_search('UiMapID', $mapGroupMemberTableHeaders);

        // Parse all UI Map Assignment TABLE data and convert them to a workable format
        $uiMapAssignmentTable       = $request->get('ui_map_assignment_table_xhr_response');
        $uiMapAssignmentTableParsed = json_decode($uiMapAssignmentTable, true)['data'];

        $uiMapAssignmentTableHeaders               = [
            'UiMin[0]', 'UiMin[1]', 'UiMax[0]', 'UiMax[1]', 'Region[0]', 'Region[1]', 'Region[2]',
            'Region[3]', 'Region[4]', 'Region[5]', 'ID', 'UiMapID', 'OrderIndex', 'MapID', 'AreaID',
            'WMODoodadPlacementID', 'WMOGroupID',
        ];
        $uiMapAssignmentTableHeaderIndexMapId      = array_search('MapID', $uiMapAssignmentTableHeaders);
        $uiMapAssignmentTableHeaderIndexUiMapId    = array_search('UiMapID', $uiMapAssignmentTableHeaders);
        $uiMapAssignmentTableHeaderIndexOrderIndex = array_search('OrderIndex', $uiMapAssignmentTableHeaders);
        $uiMapAssignmentTableHeaderIndexMinX       = array_search('Region[0]', $uiMapAssignmentTableHeaders);
        $uiMapAssignmentTableHeaderIndexMinY       = array_search('Region[1]', $uiMapAssignmentTableHeaders);
        $uiMapAssignmentTableHeaderIndexMaxX       = array_search('Region[3]', $uiMapAssignmentTableHeaders);
        $uiMapAssignmentTableHeaderIndexMaxY       = array_search('Region[4]', $uiMapAssignmentTableHeaders);

        /** @var Collection|Dungeon[] $allDungeons */
//        $allDungeons = Dungeon::where('key', Dungeon::DUNGEON_AZJOL_NERUB)->get()->keyBy('id');
        $allDungeons = Dungeon::where('map_id', '>', 0)->get()->keyBy('id');

        $changedDungeons = collect();

        // Go over the Map TABLE and parse the map_id - it's the only thing we're interested in
        foreach ($mapTableParsed as $mapTableRow) {
            foreach ($allDungeons as $dungeon) {
                // The map names don't always match up (the combined dungeons such as Karazhan seem problematic, have to do this by hand)
                $mapId = (int)$mapTableRow[$mapTableHeaderIndexMapId];
                if (trim($mapTableRow[$mapTableHeaderIndexMapName]) === __($dungeon->name)) {
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
        foreach ($allDungeons as $dungeon) {
            if ($dungeon->floors->count() === 0) {
                dump(sprintf('Skipping dungeon %s - no floors defined so no point', __($dungeon->name)));
                continue;
            }
            foreach ($uiMapAssignmentTableParsed as $uiMapAssignmentRow) {
                if ((int)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMapId] === $dungeon->map_id &&
                    (int)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexOrderIndex] === 0) {
                    // Now that we know the UI map ID - cross-reference it with the map group to get the correct floor
                    $uiMapId = (int)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexUiMapId];

                    // Try to find the map group member (floor definition) - NOTE: sometimes this doesn't exist,
                    // and you'll have to manually verify it without!
                    $foundMapGroupMember = false;
                    foreach ($mapGroupMemberTableParsed as $mapGroupMemberRow) {
                        if ((int)$mapGroupMemberRow[$mapGroupMemberTableHeaderIndexUiMapId] === $uiMapId) {
                            // We found the group member - now find which floor it was for
                            $mapGroupMemberFloorName = html_entity_decode(
                                trim($mapGroupMemberRow[$mapGroupMemberTableHeaderIndexNameLang]),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            $foundFloor              = false;
                            foreach ($dungeon->floors as $floor) {
                                if ($mapGroupMemberFloorName === __($floor->name)) {
                                    $beforeModel = clone $floor;

                                    $floor->update([
                                        'ingame_min_x' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinX],
                                        'ingame_min_y' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinY],
                                        'ingame_max_x' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxX],
                                        'ingame_max_y' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxY],
                                    ]);

                                    dump('Updated floor ' . __($floor->name));

                                    $this->mappingChanged($beforeModel, $floor);
                                    $foundFloor = true;
                                    break;
                                }
                            }

                            if (!$foundFloor) {
                                dump(
                                    sprintf('Unable to find floor with id %d and name %s in dungeon %s (typo in name or floor does not exist?)',
                                        (int)$mapGroupMemberRow[$mapGroupMemberTableHeaderIndexUiMapId],
                                        __($dungeon->name),
                                        $mapGroupMemberFloorName
                                    )
                                );
                            }

                            $foundMapGroupMember = true;
                            break;
                        }
                    }

                    if (!$foundMapGroupMember) {
                        if ($dungeon->floors->count() === 1) {
                            $floor       = $dungeon->floors->first();
                            $beforeModel = clone $floor;

                            $floor->update([
                                'ingame_min_x' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinX],
                                'ingame_min_y' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinY],
                                'ingame_max_x' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxX],
                                'ingame_max_y' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxY],
                            ]);

                            dump('Updated floor from backup: ' . __($floor->name));

                            $this->mappingChanged($beforeModel, $floor);
                        } else {
                            dump(sprintf('Unable to find map group member with ui map id %d (%s)',
                                (int)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexUiMapId],
                                __($dungeon->name)
                            ), [
                                'ingame_min_x' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinX],
                                'ingame_min_y' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinY],
                                'ingame_max_x' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxX],
                                'ingame_max_y' => $uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxY],
                            ]);
                        }
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
            $mdtNpcs = (new MDTDungeon($dungeon))->getMDTNPCs();

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
                    /** @var NpcEnemyForces $npcEnemyForces */
                    $npcEnemyForces = $npc->enemyForcesByMappingVersion()->first();
                    if ($npcEnemyForces->enemy_forces !== $mdtNpc->getCount()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces'), $mdtNpc->getId(), $mdtNpc->getCount(), $npcEnemyForces->enemy_forces),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npcEnemyForces->enemy_forces, 'new' => $mdtNpc->getCount()]
                            )
                        );
                    }

                    // Match enemy forces teeming
                    if ($npcEnemyForces->enemy_forces_teeming !== $mdtNpc->getCountTeeming()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces_teeming',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces_teeming'), $mdtNpc->getId(), $mdtNpc->getCountTeeming(), $npcEnemyForces->enemy_forces_teeming),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npcEnemyForces->enemy_forces_teeming, 'new' => $mdtNpc->getCountTeeming()]
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
                $npc->enemyForces->enemy_forces = $value;
                $npc->enemyForces->save();
                break;
            case 'mismatched_enemy_forces_teeming':
                $npc->enemyForces->enemy_forces_teeming = $value;
                $npc->enemyForces->save();
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
