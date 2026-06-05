<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTStringException;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\MDTImport;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcType;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\MDTExportStringServiceInterface;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\MDT\MDTMappingExportServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use App\Service\MDT\MDTMappingVersionServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class AdminToolsMdtController extends Controller
{
    public function mdtview(): View
    {
        return view('admin.tools.mdt.string');
    }

    public function mdtviewsubmit(
        Request                         $request,
        MDTImportStringServiceInterface $mdtImportStringService,
    ): JsonResponse {
        return response()->json(
            $mdtImportStringService
                ->setEncodedString($request->get('import_string'))
                ->getDecoded(),
        );
    }

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
    public function mdtviewasdungeonroutesubmit(
        Request                         $request,
        MDTImportStringServiceInterface $mdtImportStringService,
    ) {
        try {
            $dungeonRoute = $mdtImportStringService
                ->setEncodedString($request->get('import_string'))
                ->getDungeonRoute(collect(), collect());
            $dungeonRoute->makeVisible([
                'affixes',
                'killZones',
            ]);

            dd($dungeonRoute);
        } catch (InvalidMDTStringException) {
            abort(400, __('controller.admintools.error.mdt_string_format_not_recognized'));
        } catch (Exception $ex) {
            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('controller.admintools.error.invalid_mdt_string_exception'), $ex->getMessage());
            } else {
                $message = __('controller.admintools.error.invalid_mdt_string');
            }

            abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                abort(500, __('controller.admintools.error.mdt_importer_not_configured'));
            }

            throw $error;
        }
    }

    public function mdtviewasstring(): View
    {
        return view('admin.tools.mdt.dungeonroute');
    }

    /**
     * @return never|void
     *
     * @throws Throwable
     */
    public function mdtviewasstringsubmit(
        Request                         $request,
        MDTImportStringServiceInterface $mdtImportStringService,
        MDTExportStringServiceInterface $mdtExportStringService,
    ) {
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

            abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                abort(500, __('controller.admintools.error.mdt_importer_not_configured'));
            }

            throw $error;
        }
    }

    public function mdtdungeonmappinghash(): View
    {
        return view('admin.tools.mdt.dungeonmappinghash');
    }

    /**
     * @throws Throwable
     */
    public function mdtdungeonmappinghashsubmit(
        Request                          $request,
        MDTMappingImportServiceInterface $mdtMappingService,
    ): void {
        $dungeon = Dungeon::findOrFail($request->get('dungeon_id'));

        dd($mdtMappingService->getMDTMappingHash($dungeon->key));
    }

    public function dungeonmappingversiontomdtmapping(): View
    {
        return view('admin.tools.mdt.dungeonmappingversiontomdtmapping', [
            'mappingVersionsSelect' => MappingVersion::orderBy('dungeon_id')
                ->get()
                ->groupBy('dungeon_id')
                ->mapWithKeys(static function (Collection $mappingVersionByDungeon, int $id) {
                    $dungeon = Dungeon::findOrFail($id);

                    return [
                        __($dungeon->name) => $mappingVersionByDungeon->mapWithKeys(static fn(
                            MappingVersion $mappingVersion,
                        ) => [
                            $mappingVersion->id => $mappingVersion->getPrettyName(),
                        ]),
                    ];
                }),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function dungeonmappingversiontomdtmappingsubmit(
        Request                          $request,
        MDTMappingExportServiceInterface $mdtMappingService,
    ): void {
        $mappingVersion = MappingVersion::findOrFail($request->get('mapping_version_id'));

        echo $mdtMappingService->getMDTMappingAsLuaString($mappingVersion);
        dd();
    }

    public function dungeonMappingVersionAccuracy(
        Request                           $request,
        MDTMappingVersionServiceInterface $mappingVersionService,
    ): View {
        /** @var Collection<Dungeon> $allDungeons */
        $allDungeons = Dungeon::with(['mappingVersions', 'mappingVersions.dungeon'])->get();

        $dungeonAccuracyByFloor = collect();
        foreach ($allDungeons as $dungeon) {
            /**
             * @var MappingVersion $latestMappingVersion I load the latest mapping version for this dungeon regardless
             *                     of game version because we want to compare to the latest MDT version. The newest mapping version will
             *                     generally be the mapping version that was used in the most recent version of MDT.
             */
            $latestMappingVersion = $dungeon->mappingVersions->first();

            $dungeonAccuracyByFloor->put(
                $dungeon->id,
                $mappingVersionService->getMappingVersionAccuracy(
                    $latestMappingVersion,
                ),
            );
        }

        return view('admin.tools.mdt.dungeonmappingversionaccuracy', [
            'dungeonAccuracyByFloor' => $dungeonAccuracyByFloor,
            'dungeonsById'           => $allDungeons->keyBy('id'),
        ]);
    }

    public function mdtdiff(
        CacheServiceInterface       $cacheService,
        CoordinatesServiceInterface $coordinatesService,
    ): View {
        $warnings = new Collection();
        $npcs     = Npc::with([
            'npcEnemyForces',
            'enemies',
            'type',
        ])->get();

        // For each dungeon
        foreach (Dungeon::all() as $dungeon) {
            /** @var Dungeon $dungeon */
            $mdtNpcs = new MDTDungeon($cacheService, $coordinatesService, $dungeon)->getMDTNPCs();

            // For each NPC that is found in the MDT Dungeon
            foreach ($mdtNpcs as $mdtNpc) {
                // Ignore mobs we should ignore
                if (!$mdtNpc->isValid() || $mdtNpc->isAwakened()) {
                    continue;
                }

                // Find our own NPC
                /** @var Npc|null $npc */
                $npc = $npcs->where('id', $mdtNpc->getId())->first();

                // Not found..
                if ($npc === null) {
                    $warnings->push(
                        new ImportWarning(
                            'missing_npc',
                            sprintf(__('controller.admintools.error.mdt_unable_to_find_npc_for_id'), $mdtNpc->getId()),
                            [
                                'mdt_npc' => (object)$mdtNpc->getRawMdtNpc(),
                                'npc'     => $npc,
                            ],
                        ),
                    );
                } // Found, compare
                else {
                    // Match health
                    $npcHealth = $npc->getHealthByGameVersion(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL));
                    if ($npcHealth?->health !== $mdtNpc->getHealth()) {
                        $warnings->push(
                            new ImportWarning(
                                'mismatched_health',
                                sprintf(__('controller.admintools.error.mdt_mismatched_health'), $mdtNpc->getId(), $mdtNpc->getHealth(), $npcHealth->health),
                                [
                                    'mdt_npc' => (object)$mdtNpc->getRawMdtNpc(),
                                    'npc'     => $npc,
                                    'old'     => $npcHealth->health,
                                    'new'     => $mdtNpc->getHealth(),
                                ],
                            ),
                        );
                    }

                    // Match enemy forces
                    /** @var NpcEnemyForces|null $npcEnemyForces */
                    $npcEnemyForces = $npc->enemyForcesByMappingVersion();
                    if ($npcEnemyForces?->enemy_forces !== $mdtNpc->getCount()) {
                        $warnings->push(
                            new ImportWarning(
                                'mismatched_enemy_forces',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces'), $mdtNpc->getId(), $mdtNpc->getCount(), $npcEnemyForces?->enemy_forces),
                                [
                                    'mdt_npc' => (object)$mdtNpc->getRawMdtNpc(),
                                    'npc'     => $npc,
                                    'old'     => $npcEnemyForces?->enemy_forces,
                                    'new'     => $mdtNpc->getCount(),
                                ],
                            ),
                        );
                    }

                    // Match enemy forces teeming
                    if ($npcEnemyForces?->enemy_forces_teeming !== $mdtNpc->getCountTeeming()) {
                        $warnings->push(
                            new ImportWarning(
                                'mismatched_enemy_forces_teeming',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces_teeming'), $mdtNpc->getId(), $mdtNpc->getCountTeeming(), $npcEnemyForces?->enemy_forces_teeming),
                                [
                                    'mdt_npc' => (object)$mdtNpc->getRawMdtNpc(),
                                    'npc'     => $npc,
                                    'old'     => $npcEnemyForces?->enemy_forces_teeming,
                                    'new'     => $mdtNpc->getCountTeeming(),
                                ],
                            ),
                        );
                    }

                    // Match clone count, should be equal
                    if ($npc->enemies->count() !== count($mdtNpc->getClones())) {
                        $warnings->push(
                            new ImportWarning(
                                'mismatched_enemy_count',
                                sprintf(
                                    __('controller.admintools.error.mdt_mismatched_enemy_count'),
                                    $mdtNpc->getId(),
                                    count($mdtNpc->getClones()),
                                    $npc->enemies->count(),
                                ),
                                [
                                    'mdt_npc' => (object)$mdtNpc->getRawMdtNpc(),
                                    'npc'     => $npc,
                                ],
                            ),
                        );
                    }

                    // Match npc type, should be equal
                    if ($npc->type->type !== $mdtNpc->getCreatureType()) {
                        $warnings->push(
                            new ImportWarning(
                                'mismatched_enemy_type',
                                sprintf(
                                    __('controller.admintools.error.mdt_mismatched_enemy_type'),
                                    $mdtNpc->getId(),
                                    $mdtNpc->getCreatureType(),
                                    $npc->type->type,
                                ),
                                [
                                    'mdt_npc' => (object)$mdtNpc->getRawMdtNpc(),
                                    'npc'     => $npc,
                                    'old'     => $npc->type->type,
                                    'new'     => $mdtNpc->getCreatureType(),
                                ],
                            ),
                        );
                    }
                }
            }
        }

        return view('admin.tools.mdt.diff', ['warnings' => $warnings]);
    }

    public function applyChange(Request $request): array
    {
        $category  = $request->get('category');
        $npcId     = $request->get('npc_id');
        $dungeonId = $request->get('dungeon_id');
        $value     = $request->get('value');

        /** @var Npc $npc */
        $npc     = Npc::with(['enemyForces'])->find($npcId);
        $dungeon = Dungeon::findOrFail($dungeonId);

        switch ($category) {
            case 'mismatched_health':
                $npc->getHealthByGameVersion(
                    GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL),
                )?->update([
                    'health' => $value,
                ]);
                break;
            case 'mismatched_enemy_forces':
                $npc->setEnemyForces($value, $dungeon->getCurrentMappingVersion());

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
}
