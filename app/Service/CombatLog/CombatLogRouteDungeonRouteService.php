<?php

namespace App\Service\CombatLog;

use App\Http\Models\Request\CombatLog\Route\CombatLogRoute;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteChallengeMode;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteCoord;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteMetadata;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteNpc;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteSettings;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteSpell;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndSpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartSpecialEvent;
use App\Logic\Structs\IngameXY;
use App\Models\Brushline;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\EnemyPosition;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Polyline;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Stub\AffixGroup\AffixGroupRepository as AffixGroupRepositoryStub;
use App\Repositories\Stub\DungeonRoute\DungeonRouteAffixGroupRepository as DungeonRouteAffixGroupRepositoryStub;
use App\Repositories\Stub\DungeonRoute\DungeonRouteRepository as DungeonRouteRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneEnemyRepository as KillZoneEnemyRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneRepository as KillZoneRepositoryStub;
use App\Repositories\Stub\KillZone\KillZoneSpellRepository as KillZoneSpellRepositoryStub;
use App\Service\CombatLog\Builders\CombatLogRouteCombatLogEventsBuilder;
use App\Service\CombatLog\Builders\CombatLogRouteCorrectionBuilder;
use App\Service\CombatLog\Builders\CombatLogRouteDungeonRouteBuilder;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Logging\CombatLogRouteDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeEnd as ChallengeModeEndResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeStart as ChallengeModeStartResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged as EnemyEngagedResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyKilled as EnemyKilledResultEvent;
use App\Service\CombatLog\ResultEvents\SpellCast;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class CombatLogRouteDungeonRouteService implements CombatLogRouteDungeonRouteServiceInterface
{
    public function __construct(
        protected CombatLogService                               $combatLogService,
        protected SeasonServiceInterface                         $seasonService,
        protected CoordinatesServiceInterface                    $coordinatesService,
        protected DungeonRouteRepositoryInterface                $dungeonRouteRepository,
        protected DungeonRouteAffixGroupRepositoryInterface      $dungeonRouteAffixGroupRepository,
        protected AffixGroupRepositoryInterface                  $affixGroupRepository,
        protected KillZoneRepositoryInterface                    $killZoneRepository,
        protected KillZoneEnemyRepositoryInterface               $killZoneEnemyRepository,
        protected KillZoneSpellRepositoryInterface               $killZoneSpellRepository,
        protected CombatLogRouteDungeonRouteServiceLoggingInterface $log
    ) {
    }

    /**
     * @throws DungeonNotSupportedException
     * @throws Exception
     */
    public function convertCombatLogRouteToDungeonRoute(CombatLogRoute $combatLogRoute): DungeonRoute
    {
        $dungeonRoute = (new CombatLogRouteDungeonRouteBuilder(
            $this->seasonService,
            $this->coordinatesService,
            $this->dungeonRouteRepository,
            $this->dungeonRouteAffixGroupRepository,
            $this->affixGroupRepository,
            $this->killZoneRepository,
            $this->killZoneEnemyRepository,
            $this->killZoneSpellRepository,
            $combatLogRoute
        ))->build();

        $this->saveChallengeModeRun($combatLogRoute, $dungeonRoute);

        if ($combatLogRoute->settings->debugIcons) {
            $this->generateMapIcons(
                $dungeonRoute->mappingVersion,
                $combatLogRoute,
                $dungeonRoute
            );
        }

        return $dungeonRoute;
    }

    /**
     * @throws DungeonNotSupportedException
     * @throws Exception
     */
    public function convertCombatLogRouteToCombatLogEvents(CombatLogRoute $combatLogRoute): Collection
    {
        $builder = new CombatLogRouteCombatLogEventsBuilder(
            $this->seasonService,
            $this->coordinatesService,
            new DungeonRouteRepositoryStub(),
            new DungeonRouteAffixGroupRepositoryStub(),
            new AffixGroupRepositoryStub(),
            new KillZoneRepositoryStub(),
            new KillZoneEnemyRepositoryStub(),
            new KillZoneSpellRepositoryStub(),
            $combatLogRoute
        );

        $builder->build();

        return $builder->getCombatLogEvents();
    }

    public function correctCombatLogRoute(CombatLogRoute $combatLogRoute): CombatLogRoute
    {
        $builder = new CombatLogRouteCorrectionBuilder(
            $this->seasonService,
            $this->coordinatesService,
            new DungeonRouteRepositoryStub(),
            new DungeonRouteAffixGroupRepositoryStub(),
            new AffixGroupRepositoryStub(),
            new KillZoneRepositoryStub(),
            new KillZoneEnemyRepositoryStub(),
            new KillZoneSpellRepositoryStub(),
            $combatLogRoute
        );

        $builder->build();

        return $builder->getCombatLogRoute();
    }


    /**
     * @throws Exception
     */
    public function getCombatLogRoute(string $combatLogFilePath): CombatLogRoute
    {
        ini_set('max_execution_time', 1800);

        try {
            $this->log->getCombatLogRouteStart($combatLogFilePath);

            $dungeonRoute = null;
            $resultEvents = $this->combatLogService->getResultEventsForChallengeMode($combatLogFilePath, $dungeonRoute);
            if (!($dungeonRoute instanceof DungeonRoute)) {
                throw new Exception('Unable to generate dungeon route from combat log!');
            }

            // #1818 Filter out any NPC ids that are invalid
            $validNpcIds = $dungeonRoute->dungeon->getInUseNpcIds();

            /** @var ChallengeModeStartSpecialEvent $challengeModeStartEvent */
            $challengeModeStartEvent = $resultEvents->filter(static fn(BaseResultEvent $resultEvent) => $resultEvent instanceof ChallengeModeStartResultEvent)->first()->getChallengeModeStartEvent();

            /** @var ChallengeModeEndSpecialEvent $challengeModeEndEvent */
            $challengeModeEndEvent = $resultEvents->filter(static fn(BaseResultEvent $resultEvent) => $resultEvent instanceof ChallengeModeEndResultEvent)->first()->getChallengeModeEndEvent();

            $challengeMode = new CombatLogRouteChallengeMode(
                $challengeModeStartEvent->getTimestamp()->format(CombatLogRoute::DATE_TIME_FORMAT),
                $challengeModeEndEvent->getTimestamp()->format(CombatLogRoute::DATE_TIME_FORMAT),
                $challengeModeEndEvent->getSuccess(),
                $challengeModeEndEvent->getTotalTimeMS(),
                $challengeModeStartEvent->getChallengeModeID(),
                $challengeModeStartEvent->getKeystoneLevel(),
                $challengeModeStartEvent->getAffixIDs()
            );

            $npcs             = collect();
            $npcEngagedEvents = collect();
            $spells           = collect();
            foreach ($resultEvents as $resultEvent) {
                if ($resultEvent instanceof EnemyEngagedResultEvent) {
                    $guid = $resultEvent->getGuid();
                    if ($validNpcIds->search($guid->getId()) === false) {
                        $this->log->getCombatLogRouteEnemyEngagedInvalidNpcId($guid->getId());

                        continue;
                    }

                    $npcEngagedEvents->put($guid->getGuid(), $resultEvent);
                } else if ($resultEvent instanceof EnemyKilledResultEvent) {
                    $guid = $resultEvent->getGuid();
                    if ($validNpcIds->search($guid->getId()) === false) {
                        $this->log->getCombatLogRouteEnemyKilledInvalidNpcId($guid->getId());

                        continue;
                    }

                    /** @var EnemyEngagedResultEvent $npcEngagedEvent */
                    $npcEngagedEvent = $npcEngagedEvents->get($guid->getGuid());

                    $npcEngagedEvents->forget($guid->getGuid());

                    $npcs->push(
                        new CombatLogRouteNpc(
                            $guid->getId(),
                            $guid->getSpawnUID(),
                            $npcEngagedEvent->getEngagedEvent()->getTimestamp()->format(CombatLogRoute::DATE_TIME_FORMAT),
                            $resultEvent->getBaseEvent()->getTimestamp()->format(CombatLogRoute::DATE_TIME_FORMAT),
                            new CombatLogRouteCoord(
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getPositionX(),
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getPositionY(),
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getUiMapId()
                            )
                        )
                    );
                } else if ($resultEvent instanceof SpellCast) {
                    /** @var Player $guid */
                    $advancedData = $resultEvent->getAdvancedCombatLogEvent()->getAdvancedData();

                    $spells->push(
                        new CombatLogRouteSpell(
                            $resultEvent->getSpellId(),
                            $advancedData->getInfoGuid()->getGuid(),
                            $resultEvent->getBaseEvent()->getTimestamp()->format(CombatLogRoute::DATE_TIME_FORMAT),
                            new CombatLogRouteCoord(
                                $advancedData->getPositionX(),
                                $advancedData->getPositionY(),
                                $advancedData->getUiMapId()
                            )
                        )
                    );
                }
            }

            if ($npcEngagedEvents->isNotEmpty()) {
                throw new Exception("Found enemies that weren't killed!");
            }

            return new CombatLogRoute(
                new CombatLogRouteMetadata(Uuid::uuid4()->toString()),
                new CombatLogRouteSettings(true, true),
                $challengeMode,
                $npcs,
                $spells
            );

        } finally {
            $this->log->getCombatLogRouteEnd();
        }
    }

    private function saveChallengeModeRun(CombatLogRoute $combatLogRoute, DungeonRoute $dungeonRoute): void
    {
        // Insert a run
        $now = Carbon::now();

        /** @var ChallengeModeRun $challengeModeRun */
        $challengeModeRun = ChallengeModeRun::create([
            'dungeon_id'       => $dungeonRoute->dungeon_id,
            'dungeon_route_id' => $dungeonRoute->id,
            'level'            => $combatLogRoute->challengeMode->level,
            'success'          => $combatLogRoute->challengeMode->success,
            'total_time_ms'    => $combatLogRoute->challengeMode->durationMs,
            'created_at'       => $now,
        ]);

        $floorByUiMapId = Floor::where('dungeon_id', $dungeonRoute->dungeon_id)
            ->get()
            ->keyBy('ui_map_id');

        $invalidUiMapIds         = [];
        $enemyPositionAttributes = [];
        foreach ($combatLogRoute->npcs as $npc) {
            /** @var Floor $floor */
            $floor = $floorByUiMapId->get($npc->coord->uiMapId);

            if ($floor === null) {
                if (!in_array($npc->coord->uiMapId, $invalidUiMapIds)) {
                    $this->log->saveChallengeModeRunUnableToFindFloor($npc->coord->uiMapId);
                    $invalidUiMapIds[] = $npc->coord->uiMapId;
                }

                continue;
            }

            $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                new IngameXY($npc->coord->x, $npc->coord->y, $floor)
            );

            $enemyPositionAttributes[] = array_merge([
                'challenge_mode_run_id' => $challengeModeRun->id,
                'floor_id'              => $floor->id,
                'npc_id'                => $npc->npcId,
                'guid'                  => $npc->getUniqueId(),
                'created_at'            => $now,
            ], $latLng->toArray());
        }

        if (EnemyPosition::insertOrIgnore($enemyPositionAttributes) === 0) {
            // Then we don't want duplicates - get rid of the challenge mode run
            $challengeModeRun->update([
                'duplicate' => 1,
            ]);
        }

        ChallengeModeRunData::create([
            'challenge_mode_run_id' => $challengeModeRun->id,
            'run_id'                => $combatLogRoute->metadata->runId,
            'correlation_id'        => correlationId(),
            'post_body'             => json_encode($combatLogRoute),
        ]);
    }

    private function generateMapIcons(
        MappingVersion $mappingVersion,
        CombatLogRoute $combatLogRoute,
        ?DungeonRoute  $dungeonRoute = null
    ): void {
        $now                 = now();
        $mapIconAttributes   = [];
        $polylineAttributes  = [];
        $brushlineAttributes = [];

        $npcs          = $dungeonRoute->dungeon->getInUseNpcs()->keyBy('id');
        $validNpcIds   = $dungeonRoute->dungeon->getInUseNpcIds($npcs);
        $previousFloor = null;
        foreach ($combatLogRoute->npcs as $combatLogRouteNpc) {
            $currentFloor = $combatLogRouteNpc->getResolvedEnemy()?->floor ?? $previousFloor;

            if ($currentFloor === null) {
                $this->log->generateMapIconsUnableToFindFloor($combatLogRouteNpc->getUniqueId());

                continue;
            }

            $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                new IngameXY(
                    $combatLogRouteNpc->coord->x,
                    $combatLogRouteNpc->coord->y,
                    $currentFloor
                )
            );

            /** @var Npc|null $npc */
            $npc     = $npcs->get($combatLogRouteNpc->npcId);
            $comment = json_encode(['name' => __($npc?->name ?? 'Npc not found', [], 'en_US')] + $combatLogRouteNpc->toArray());

            $hasResolvedEnemy = $combatLogRouteNpc->getResolvedEnemy() !== null;

            $mapIconAttributes[] = array_merge([
                'mapping_version_id' => null,
                'floor_id'           => $currentFloor->id,
                'dungeon_route_id'   => $dungeonRoute?->id ?? null,
                'team_id'            => null,
                'map_icon_type_id'   => MapIconType::ALL[$hasResolvedEnemy && $validNpcIds->search($combatLogRouteNpc->npcId) !== false ?
                    MapIconType::MAP_ICON_TYPE_DOT_YELLOW :
                    MapIconType::MAP_ICON_TYPE_NEONBUTTON_RED],
                'comment'            => $comment,
                'permanent_tooltip'  => 0,
            ], $latLng->toArray());

            if ($hasResolvedEnemy) {
                $brushlineAttributes[] = [
                    'dungeon_route_id' => $dungeonRoute?->id ?? null,
                    'floor_id'         => $currentFloor->id,
                    'polyline_id'      => -1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];

                $polylineAttributes[] = [
                    'model_class'   => Brushline::class,
                    'color'         => '#f202fa',
                    'weight'        => 2,
                    'vertices_json' => json_encode([
                        $latLng->toArray(),
                        $combatLogRouteNpc->getResolvedEnemy()->getLatLng()->toArray(),
                    ]),
                ];
            }

            $previousFloor = $currentFloor;
        }

        MapIcon::insert($mapIconAttributes);
        Brushline::insert($brushlineAttributes);

        // Assign the paths to the polylines
        $dungeonRoute->load('brushlines');

        $index = 0;
        foreach ($dungeonRoute->brushlines as $brushline) {
            $polylineAttributes[$index]['model_id'] = $brushline->id;

            $index++;
        }

        Polyline::insert($polylineAttributes);

        // Assign the polylines back to the brushlines/paths
        $polyLines = Polyline::where(static function (Builder $builder) use ($dungeonRoute) {
            $builder->whereIn('model_id', $dungeonRoute->brushlines->pluck('id'))
                ->where('model_class', Brushline::class);
        })->orderBy('id')
            ->get('id');

        $polyLineIndex = 0;
        foreach ($dungeonRoute->brushlines as $brushline) {
            $brushline->update(['polyline_id' => $polyLines->get($polyLineIndex)->id]);

            $polyLineIndex++;
        }
    }
}
