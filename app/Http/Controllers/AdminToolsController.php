<?php

/** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Jobs\RefreshEnemyForces;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTStringException;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\MDTImport;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcType;
use App\Models\Spell\Spell;
use App\Service\Cache\CacheServiceInterface;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\DungeonRoute\ThumbnailService;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\MDT\MDTMappingExportServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use App\Service\MessageBanner\MessageBannerServiceInterface;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use App\Traits\SavesArrayToJsonFile;
use Artisan;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Session;
use Throwable;

class AdminToolsController extends Controller
{
    use ChangesMapping;
    use SavesArrayToJsonFile;

    /**
     * @return Factory|View
     */
    public function index(): View
    {
        return view('admin.tools.list');
    }

    public function combatlog(
        MapContextServiceInterface              $mapContextService,
        ResultEventDungeonRouteServiceInterface $combatLogDungeonRouteService
    ): View {
        try {
            $dungeonRoutes = $combatLogDungeonRouteService->convertCombatLogToDungeonRoutes(
            //                '/mnt/volume1/media/WoW/combatlogs/DF_S2/WoWCombatLog-050623_221451_19_court-of-stars.zip'
            //                    '/mnt/volume1/media/WoW/combatlogs/DF_S2/WoWCombatLog-050923_172619_10_uldaman-legacy-of-tyr.zip',
                base_path(
                //                    'WoWCombatLog-050923_172619_7_freehold.zip'
                //                    'WoWCombatLog-050923_172619_10_uldaman-legacy-of-tyr.zip'
                //                    'WoWCombatLog-050923_172619_12_neltharions-lair.zip'
                //                    'WoWCombatLog-051023_160438_14_the-underrot.zip'
                //                    'WoWCombatLog-051223_185606_14_brackenhide-hollow.zip'
                //                    'WoWCombatLog-060223_181049_20_brackenhide-hollow.zip',
                //                    'WoWCombatLog-051023_175258_17_the-vortex-pinnacle.zip',
                //                    'WoWCombatLog-060223_181049_20_halls-of-infusion.zip',
                //                'WoWCombatLog-051323_095734_13_neltharus.zip',
                //                'WoWCombatLog-060223_181049_20_neltharus.zip'
                    'tests/CombatLogs/WoWCombatLog-050923_172619_7_freehold.zip',
                //                    'tests/CombatLogs/WoWCombatLog-050923_172619_7_freehold_events.txt'
                //                'tests/Unit/App/Service/CombatLog/Fixtures/2_underrot/WoWCombatLog-051523_211651_2_the-underrot.txt'
                //                'tests/Unit/App/Service/CombatLog/Fixtures/2_underrot/combat.log'
                //                'tests/Unit/App/Service/CombatLog/Fixtures/18_neltharions_lair/combat.log'
                //                    'tests/Unit/App/Service/CombatLog/Fixtures/18_the_vortex_pinnacle/combat.log'
                )
            );
        } catch (Exception $exception) {
            dd($exception);
        }

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = $dungeonRoutes->first();

        // Reload to re-populate all kinds of fields
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::find($dungeonRoute->id);

        /** @var Floor $floor */
        $floor = $dungeonRoute->dungeon->floors()->first();

        return view('dungeonroute.edit', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floor'        => $floor,
            'mapContext'   => $mapContextService->createMapContextDungeonRoute($dungeonRoute, $floor),
            'floorIndex'   => 1,
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function messageBanner(): View
    {
        return view('admin.tools.messagebanner.set');
    }

    /**
     * @return RedirectResponse
     */
    public function messageBannerSubmit(
        Request                       $request,
        MessageBannerServiceInterface $messageBannerService
    ): RedirectResponse {
        $message = $request->get('message');
        $messageBannerService->setMessage(empty($message) ? null : $message);

        Session::flash('status', __('controller.admintools.flash.message_banner_set_successfully'));

        return redirect()->route('admin.tools.messagebanner.set');
    }

    /**
     * @return Application|Factory|View
     */
    public function npcimport(): View
    {
        return view('admin.tools.npc.import');
    }

    /**
     * @return void
     */
    public function npcimportsubmit(Request $request): void
    {
        $importString = $request->get('import_string');

        // Correct the string since wowhead sucks
        $importString = str_replace('[Listview.extraCols.popularity]', '["Listview.extraCols.popularity"]', (string)$importString);

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

        $classificationMapping = [
            0 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
            1 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
            2 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE],
            3 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
            4 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE],
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
                $npcCandidate->classification_id = $classificationMapping[($npcData['classification'] ?? 0) + ($npcData['boss'] ?? 0) + 1];
                // Bosses
                if ($npcCandidate->classification_id >= NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                    $npcCandidate->dangerous = true;
                }

                $npcCandidate->npc_type_id = $npcTypeMapping[$npcData['type']];
                // 8 since we start the expansion with 8 dungeons usually
                $npcCandidate->dungeon_id = count($npcData['location']) > 1 ? -1 : $dungeon->id;
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
        } catch (Exception $exception) {
            dump($exception);
        } finally {
            dump($log);
        }
    }

    /**
     * @param Dungeon|null $dungeon
     * @return View
     */
    public function manageSpellVisibility(Request $request, ?Dungeon $dungeon = null): View
    {
        return view('admin.tools.npc.managespellvisibility', [
            'npcs'    => Npc::when($dungeon !== null, function (Builder $builder) use ($dungeon) {
                return $builder->where('dungeon_id', $dungeon->id);
            })->with('npcSpells')
                ->has('npcSpells')
                ->paginate(50),
            'spells'  => Spell::with('gameVersion')->when($dungeon !== null, function (Builder $builder) use ($dungeon) {
                return $builder->whereRelation('spellDungeons', 'dungeon_id', $dungeon->id);
            })->get()
                ->keyBy('id'),
            'dungeon' => $dungeon,
        ]);
    }

    public function manageSpellVisibilitySubmit(Request $request): RedirectResponse
    {
        $dungeonId = (int)$request->get('dungeon_id');
        $dungeon   = null;
        if ($dungeonId !== -1) {
            $dungeon = Dungeon::findOrFail($dungeonId);
        }

        return redirect()->route('admin.tools.npc.managespellvisibility', ['dungeon' => $dungeon]);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function dungeonroute(): View
    {
        return view('admin.tools.dungeonroute.view');
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function dungeonroutesubmit(Request $request): View
    {
        $publicKey = $request->get('public_key');

        $dungeonRoute = DungeonRoute::with([
            'faction', 'specializations', 'classes', 'races', 'affixes',
            'brushlines', 'paths', 'author', 'killZones', 'pridefulEnemies', 'publishedstate',
            'ratings', 'favorites', 'enemyraidmarkers', 'mapicons', 'mdtImport', 'team',
        ])->when(is_numeric($publicKey), function (Builder $builder) use ($publicKey) {
            $builder->where('id', $publicKey);
        }, function (Builder $builder) use ($publicKey) {
            $builder->where('public_key', $publicKey);
        })->firstOrFail();

        return view('admin.tools.dungeonroute.viewcontents', [
            'dungeonroute' => $dungeonRoute,
        ]);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function dungeonrouteMappingVersions(): View
    {
        $mappingVersionUsage = MappingVersion::orderBy('dungeon_id')
            ->get()
            ->mapWithKeys(static fn(MappingVersion $mappingVersion) => [$mappingVersion->getPrettyName() => $mappingVersion->dungeonRoutes()->count()])
            ->groupBy(static fn(int $count, string $key) => $count === 0, true);

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
    public function enemyforcesimport(): View
    {
        return view('admin.tools.enemyforces.import');
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function enemyforcesimportsubmit(Request $request): void
    {
        $json = json_decode((string)$request->get('import_string'), true);

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
    public function enemyforcesrecalculate(): View
    {
        return view('admin.tools.enemyforces.recalculate');
    }

    /**
     * @return void
     */
    public function enemyforcesrecalculatesubmit(Request $request): void
    {
        $dungeonId = (int)$request->get('dungeon_id');

        $builder = DungeonRoute::without(['faction', 'specializations', 'classes', 'races', 'affixes'])
            ->select('id')
            ->when($dungeonId !== -1, static fn(Builder $builder) => $builder->where('dungeon_id', $dungeonId));

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
    public function thumbnailsregenerate(): View
    {
        return view('admin.tools.thumbnails.regenerate');
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function thumbnailsregeneratesubmit(Request $request, ThumbnailService $thumbnailService): View
    {
        set_time_limit(3600);

        $dungeonId   = (int)$request->get('dungeon_id');
        $onlyMissing = (int)$request->get('only_missing');

        $builder = DungeonRoute::without(['faction', 'specializations', 'classes', 'races', 'affixes'])
            ->with('dungeon')
            ->when($dungeonId !== -1, static fn(Builder $builder) => $builder->where('dungeon_id', $dungeonId))
            ->orderByDesc('created_at');

        $successCount  = 0;
        $failureCount  = 0;
        $dungeonRoutes = $builder->get();
        foreach ($dungeonRoutes as $dungeonRoute) {
            $shouldRefresh = !$onlyMissing || !$thumbnailService->hasThumbnailsGenerated($dungeonRoute);

            if ($shouldRefresh) {
                if ($thumbnailService->queueThumbnailRefresh($dungeonRoute)) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }
        }

        Session::flash('status', __('controller.admintools.flash.thumbnail_regenerate_result', [
            'success' => $successCount,
            'total'   => $successCount + $failureCount,
            'failed'  => $failureCount,
        ]));

        return view('admin.tools.thumbnails.regenerate');
    }

    /**
     * @return Factory|
     */
    public function mdtview(): View
    {
        return view('admin.tools.mdt.string');
    }

    public function mdtviewsubmit(Request $request, MDTImportStringServiceInterface $mdtImportStringService): JsonResponse
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
    public function mdtviewasdungeonroute(): View
    {
        return view('admin.tools.mdt.string', ['asDungeonroute' => true]);
    }

    public function mdtImportList(): View
    {
        return view('admin.tools.mdt.list', [
            'mdtImports' => MDTImport::whereNotNull('error')
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }

    /**
     * @return never|void
     *
     * @throws Throwable
     */
    public function mdtviewasdungeonroutesubmit(Request $request, MDTImportStringServiceInterface $mdtImportStringService)
    {
        try {
            $dungeonRoute = $mdtImportStringService
                ->setEncodedString($request->get('import_string'))
                ->getDungeonRoute(collect(), collect());
            $dungeonRoute->makeVisible(['affixes', 'killZones']);

            dd($dungeonRoute);
        } catch (InvalidMDTStringException) {
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
    public function mdtviewasstring(): View
    {
        return view('admin.tools.mdt.dungeonroute');
    }

    /**
     * @return never|void
     *
     * @throws Throwable
     */
    public function mdtviewasstringsubmit(Request $request, MDTImportStringServiceInterface $mdtImportStringService, MDTExportStringServiceInterface $mdtExportStringService)
    {
        $publicKey = $request->get('public_key');

        $dungeonRoute = DungeonRoute::when(is_numeric($publicKey), function (Builder $builder) use ($publicKey) {
            $builder->where('id', $publicKey);
        }, function (Builder $builder) use ($publicKey) {
            $builder->where('public_key', $publicKey);
        })->firstOrFail();

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
    public function mdtdungeonmappinghash(): View
    {
        return view('admin.tools.mdt.dungeonmappinghash');
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    public function mdtdungeonmappinghashsubmit(Request $request, MDTMappingImportServiceInterface $mdtMappingService): void
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
                ->mapWithKeys(static function (Collection $mappingVersionByDungeon, int $id) {
                    $dungeon = Dungeon::findOrFail($id);

                    return [
                        __($dungeon->name) => $mappingVersionByDungeon->mapWithKeys(static fn(MappingVersion $mappingVersion) => [
                            $mappingVersion->id => $mappingVersion->getPrettyName(),
                        ]),
                    ];
                }),
        ]);
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    public function dungeonmappingversiontomdtmappingsubmit(Request $request, MDTMappingExportServiceInterface $mdtMappingService): void
    {
        $mappingVersion = MappingVersion::findOrFail($request->get('mapping_version_id'));

        echo $mdtMappingService->getMDTMappingAsLuaString($mappingVersion);
        dd();
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function wowToolsImportIngameCoordinates(): View
    {
        return view('admin.tools.wowtools.importingamecoordinates');
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     *
     * @throws Exception
     */
    public function wowToolsImportIngameCoordinatesSubmit(Request $request): void
    {
        // Parse all Map TABLE data and convert them to a workable format
        $mapTable                   = $request->get('map_table_xhr_response');
        $mapTableParsed             = json_decode((string)$mapTable, true)['data'];
        $mapTableHeaders            = [
            'ID', 'Directory', 'MapName_lang', 'MapDescription0_lang', 'MapDescription1_lang', 'PvpShortDescription_lang',
            'Corpse[0]', 'Corpse[1]', 'MapType', 'InstanceType', 'ExpansionID', 'AreaTableID', 'LoadingScreenID',
            'TimeOfDayOverride', 'ParentMapId', 'CosmeticParentMapID', 'TimeOffset', 'MinimapIconScale', 'CorpseMapID',
            'MaxPlayers', 'WindSettingsID', 'ZmpFileDataID', 'WdtFileDataID', 'NavigationMaxDistance', 'Flags[0]',
            'Flags[1]', 'Flags[2]',
        ];
        $mapTableHeaderIndexMapName = array_search('MapName_lang', $mapTableHeaders, true);
        $mapTableHeaderIndexMapId   = array_search('ID', $mapTableHeaders, true);

        // Parse all Map Group Member TABLE data and convert them to a workable format
        $mapGroupMemberTable                    = $request->get('ui_map_group_member_table_xhr_response');
        $mapGroupMemberTableParsed              = json_decode((string)$mapGroupMemberTable, true)['data'];
        $mapGroupMemberTableHeaders             = [
            'ID', 'Name_lang', 'UiMapGroupID', 'UiMapID', 'FloorIndex', 'RelativeHeightIndex',
        ];
        $mapGroupMemberTableHeaderIndexNameLang = array_search('Name_lang', $mapGroupMemberTableHeaders, true);
        $mapGroupMemberTableHeaderIndexUiMapId  = array_search('UiMapID', $mapGroupMemberTableHeaders, true);

        // Parse all UI Map Assignment TABLE data and convert them to a workable format
        $uiMapAssignmentTable       = $request->get('ui_map_assignment_table_xhr_response');
        $uiMapAssignmentTableParsed = json_decode((string)$uiMapAssignmentTable, true)['data'];

        $uiMapAssignmentTableHeaders               = [
            'UiMin[0]', 'UiMin[1]', 'UiMax[0]', 'UiMax[1]', 'Region[0]', 'Region[1]', 'Region[2]',
            'Region[3]', 'Region[4]', 'Region[5]', 'ID', 'UiMapID', 'OrderIndex', 'MapID', 'AreaID',
            'WMODoodadPlacementID', 'WMOGroupID',
        ];
        $uiMapAssignmentTableHeaderIndexMapId      = array_search('MapID', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexUiMapId    = array_search('UiMapID', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexOrderIndex = array_search('OrderIndex', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMinX       = array_search('Region[0]', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMinY       = array_search('Region[1]', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMaxX       = array_search('Region[3]', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMaxY       = array_search('Region[4]', $uiMapAssignmentTableHeaders, true);

        /** @var Collection<Dungeon> $allDungeons */
        //        $allDungeons = Dungeon::where('key', Dungeon::DUNGEON_AZJOL_NERUB)->get()->keyBy('id');
        $allDungeons = Dungeon::where('map_id', '>', 0)->get()->keyBy('id');

        $changedDungeons = collect();

        // Go over the Map TABLE and parse the map_id - it's the only thing we're interested in
        foreach ($mapTableParsed as $mapTableRow) {
            foreach ($allDungeons as $dungeon) {
                // The map names don't always match up (the combined dungeons such as Karazhan seem problematic, have to do this by hand)
                $mapId = (int)$mapTableRow[$mapTableHeaderIndexMapId];
                if (trim((string)$mapTableRow[$mapTableHeaderIndexMapName]) === __($dungeon->name)) {
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
                                trim((string)$mapGroupMemberRow[$mapGroupMemberTableHeaderIndexNameLang]),
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
                            /** @var Floor $floor */
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
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function wagoggImportIngameCoordinates(): View
    {
        return view('admin.tools.wagogg.importingamecoordinates');
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     *
     * @throws Exception
     */
    public function wagoggImportIngameCoordinatesSubmit(Request $request): void
    {
        // Parse all UI Map Assignment TABLE data and convert them to a workable format
        $uiMapAssignmentTable                   = $request->get('ui_map_assignment_table_csv');
        $uiMapAssignmentTableParsed             = str_getcsv_assoc($uiMapAssignmentTable);
        $uiMapAssignmentTableHeaders            = array_shift($uiMapAssignmentTableParsed);
        $uiMapAssignmentTableHeaderIndexUiMapId = array_search('UiMapID', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMinX    = array_search('Region_0', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMinY    = array_search('Region_1', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMaxX    = array_search('Region_3', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMaxY    = array_search('Region_4', $uiMapAssignmentTableHeaders, true);

        /** @var Collection<Floor> $allFloors */
        //        $allDungeons = Dungeon::where('key', Dungeon::DUNGEON_AZJOL_NERUB)->get()->keyBy('id');
        $allFloors = Floor::where('facade', 0)
            ->where('ui_map_id', '>', 0)
            ->where('ingame_min_x', 0)
            ->where('ingame_min_y', 0)
//            ->where('ingame_max_x', 0)
//            ->where('ingame_max_y', 0)
            ->get();

        dump('Changed floors:');

        $allUiMapIds                = $allFloors->pluck('ui_map_id')->toArray();
        $uiMapAssignmentTableParsed = array_filter($uiMapAssignmentTableParsed, function (array $item) use ($allUiMapIds, $uiMapAssignmentTableHeaderIndexUiMapId) {
            return in_array($item[$uiMapAssignmentTableHeaderIndexUiMapId], $allUiMapIds);
        });

        // Go over the UI Map Assignments and find the ones we're interested in
        foreach ($allFloors as $floor) {
            foreach ($uiMapAssignmentTableParsed as $index => $uiMapAssignmentRow) {
                if (((int)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexUiMapId]) === $floor->ui_map_id) {
                    $beforeModel = clone $floor;

                    $floor->update([
                        'ingame_min_x' => round((float)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinX], 2),
                        'ingame_min_y' => round($uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinY], 2),
                        'ingame_max_x' => round($uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxX], 2),
                        'ingame_max_y' => round($uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxY], 2),
                    ]);

                    dump(sprintf('Updated floor %s (id: %d, ui_map_id: %d) ', __($floor->name), $floor->id, $floor->ui_map_id));

//                    $this->mappingChanged($beforeModel, $floor);

                    break;
                }
            }
        }

        dd('done!');
    }

    /**
     * @return Factory|
     *
     * @throws Exception
     */
    public function mdtdiff(
        CacheServiceInterface       $cacheService,
        CoordinatesServiceInterface $coordinatesService
    ): View {
        $warnings = new Collection();
        $npcs     = Npc::with(['enemies', 'type'])->get();

        // For each dungeon
        foreach (Dungeon::all() as $dungeon) {
            /** @var Dungeon $dungeon */
            $mdtNpcs = (new MDTDungeon($cacheService, $coordinatesService, $dungeon))->getMDTNPCs();

            // For each NPC that is found in the MDT Dungeon
            foreach ($mdtNpcs as $mdtNpc) {
                // Ignore mobs we should ignore
                if (!$mdtNpc->isValid() || $mdtNpc->isAwakened()) {
                    continue;
                }

                // Find our own NPC
                /** @var \App\Models\Npc\Npc $npc */
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
                    /** @var NpcEnemyForces|null $npcEnemyForces */
                    $npcEnemyForces = $npc->enemyForcesByMappingVersion()->first();
                    if ($npcEnemyForces?->enemy_forces !== $mdtNpc->getCount()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces'), $mdtNpc->getId(), $mdtNpc->getCount(), $npcEnemyForces?->enemy_forces),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npcEnemyForces?->enemy_forces, 'new' => $mdtNpc->getCount()]
                            )
                        );
                    }

                    // Match enemy forces teeming
                    if ($npcEnemyForces?->enemy_forces_teeming !== $mdtNpc->getCountTeeming()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces_teeming',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces_teeming'), $mdtNpc->getId(), $mdtNpc->getCountTeeming(), $npcEnemyForces?->enemy_forces_teeming),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npcEnemyForces?->enemy_forces_teeming, 'new' => $mdtNpc->getCountTeeming()]
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

    public function dropCache(Request $request, CacheServiceInterface $cacheService): RedirectResponse
    {
        ini_set('max_execution_time', -1);

        $cacheService->dropCaches();

        Artisan::call('modelCache:clear');

        Artisan::call('keystoneguru:view', ['operation' => 'cache']);

        Session::flash('status', __('controller.admintools.flash.caches_dropped_successfully'));

        return redirect()->route('admin.tools');
    }

    /**
     * @return void
     */
    public function mappingForceSync(Request $request): void
    {
        Artisan::call('mapping:sync', ['--force' => true]);
    }

    /**
     * @return array
     */
    public function applychange(Request $request)
    {
        $category = $request->get('category');
        $npcId    = $request->get('npc_id');
        $value    = $request->get('value');

        /** @var \App\Models\Npc\Npc $npc */
        $npc = Npc::with(['enemyForces'])->find($npcId);

        switch ($category) {
            case 'mismatched_health':
                $npc->base_health = $value;
                $npc->save();
                break;
            case 'mismatched_enemy_forces':
                if ($npc->dungeon_id !== -1) {
                    $npc->setEnemyForces($value);
                }

                break;
            // Teeming is deprecated pretty much
            //            case 'mismatched_enemy_forces_teeming':
            //                $npc->enemyForces->enemy_forces_teeming = $value;
            //                $npc->enemyForces->save();
            //                break;
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
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function exportreleases(Request $request): View
    {
        Artisan::call('release:save');

        Session::flash('status', __('controller.admintools.flash.releases_exported'));

        return view('admin.tools.list');
    }

    public function toggleReadOnlyMode(Request $request, ReadOnlyModeServiceInterface $readOnlyModeService): RedirectResponse
    {
        if ($readOnlyModeService->isReadOnly()) {
            $readOnlyModeService->setReadOnly(false);
            Session::flash('status', __('controller.admintools.flash.read_only_mode_disabled'));
        } else {
            $readOnlyModeService->setReadOnly(true);
            Session::flash('status', __('controller.admintools.flash.read_only_mode_enabled'));
        }

        return redirect()->route('admin.tools');
    }

    /**
     * @throws Exception
     */
    public function exportdungeondata(Request $request): View
    {
        Artisan::call('mapping:save');

        return view('admin.tools.datadump.viewexporteddungeondata');
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function exceptionselect(Request $request): View
    {
        return view('admin.tools.exception.select');
    }

    /**
     * @throws TokenMismatchException
     * @throws Exception
     */
    public function exceptionselectsubmit(Request $request): void
    {
        switch ($request->get('exception')) {
            case 'TokenMismatchException':
                throw new TokenMismatchException(__('controller.admintools.flash.exception.token_mismatch'));
            case 'InternalServerError':
                throw new Exception(__('controller.admintools.flash.exception.internal_server_error'));
        }
    }

    public function listFeatures(Request $request): View
    {
        return view('admin.tools.features.list', [
            'features' => collect(ClassFinder::getClassesInNamespace('App\\Features')),
        ]);
    }

    public function toggleFeature(Request $request): RedirectResponse
    {
        $feature = (string)$request->get('feature');

        $wasActive = Feature::active($feature);
        if ($wasActive) {
            Feature::deactivateForEveryone($feature);
        } else {
            Feature::activateForEveryone($feature);
        }

        Session::flash('status', __(!$wasActive ?
            'controller.admintools.flash.feature_toggle_activated' :
            'controller.admintools.flash.feature_toggle_deactivated', [
            'feature' => $feature,
        ]));

        return redirect()->route('admin.tools.features.list');
    }

    public function forgetFeature(Request $request): RedirectResponse
    {
        $feature = (string)$request->get('feature');

        Feature::forget($feature);
        Feature::for(null)->forget($feature);

        Session::flash('status', __('controller.admintools.flash.feature_forgotten', ['feature' => $feature]));

        return redirect()->route('admin.tools.features.list');
    }
}
