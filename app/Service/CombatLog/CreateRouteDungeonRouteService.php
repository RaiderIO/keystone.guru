<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndSpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartSpecialEvent;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Builders\CreateRouteBodyDungeonRouteBuilder;
use App\Service\CombatLog\Logging\CreateRouteDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteChallengeMode;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteCoord;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteNpc;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteSettings;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteSpell;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeEnd as ChallengeModeEndResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeStart as ChallengeModeStartResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged as EnemyEngagedResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyKilled as EnemyKilledResultEvent;
use App\Service\CombatLog\ResultEvents\SpellCast;
use App\Service\Season\SeasonServiceInterface;
use Exception;

class CreateRouteDungeonRouteService implements CreateRouteDungeonRouteServiceInterface
{
    protected CombatLogService $combatLogService;

    protected SeasonServiceInterface $seasonService;

    protected CreateRouteDungeonRouteServiceLoggingInterface $log;

    /**
     * @param CombatLogService                               $combatLogService
     * @param SeasonServiceInterface                         $seasonService
     * @param CreateRouteDungeonRouteServiceLoggingInterface $log
     */
    public function __construct(
        CombatLogService $combatLogService,
        SeasonServiceInterface $seasonService,
        CreateRouteDungeonRouteServiceLoggingInterface $log
    ) {
        $this->combatLogService = $combatLogService;
        $this->seasonService    = $seasonService;
        $this->log              = $log;
    }

    /**
     * @param CreateRouteBody $createRouteBody
     *
     * @return DungeonRoute
     */
    public function convertCreateRouteBodyToDungeonRoute(CreateRouteBody $createRouteBody): DungeonRoute
    {
        $dungeonRoute = (new CreateRouteBodyDungeonRouteBuilder($this->seasonService, $createRouteBody))->build();

        if ($createRouteBody->settings->debugIcons) {
            $this->generateMapIcons(
                $dungeonRoute->mappingVersion,
                $createRouteBody,
                $dungeonRoute
            );
        }

        return $dungeonRoute;
    }

    /**
     * @param string $combatLogFilePath
     *
     * @return CreateRouteBody
     * @throws Exception
     */
    public function getCreateRouteBody(string $combatLogFilePath): CreateRouteBody
    {
        ini_set('max_execution_time', 1800);

        try {
            $this->log->getCreateRouteBodyStart($combatLogFilePath);

            $dungeonRoute = null;
            $resultEvents = $this->combatLogService->getResultEvents($combatLogFilePath, $dungeonRoute);
            if (!($dungeonRoute instanceof DungeonRoute)) {
                throw new Exception('Unable to generate dungeon route from combat log!');
            }

            // #1818 Filter out any NPC ids that are invalid
            $validNpcIds = $dungeonRoute->dungeon->getInUseNpcIds();

            /** @var ChallengeModeStartSpecialEvent $challengeModeStartEvent */
            $challengeModeStartEvent = $resultEvents->filter(function (BaseResultEvent $resultEvent) {
                return $resultEvent instanceof ChallengeModeStartResultEvent;
            })->first()->getChallengeModeStartEvent();

            /** @var ChallengeModeEndSpecialEvent $challengeModeEndEvent */
            $challengeModeEndEvent = $resultEvents->filter(function (BaseResultEvent $resultEvent) {
                return $resultEvent instanceof ChallengeModeEndResultEvent;
            })->first()->getChallengeModeEndEvent();

            $challengeMode = new CreateRouteChallengeMode(
                $challengeModeStartEvent->getTimestamp()->format(CreateRouteBody::DATE_TIME_FORMAT),
                $challengeModeEndEvent->getTimestamp()->format(CreateRouteBody::DATE_TIME_FORMAT),
                $challengeModeEndEvent->getTotalTimeMS(),
                $challengeModeStartEvent->getInstanceID(),
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
                        $this->log->getCreateRouteBodyEnemyEngagedInvalidNpcId($guid->getId());
                        continue;
                    }

                    $npcEngagedEvents->put($guid->getGuid(), $resultEvent);
                } elseif ($resultEvent instanceof EnemyKilledResultEvent) {
                    $guid = $resultEvent->getGuid();
                    if ($validNpcIds->search($guid->getId()) === false) {
                        $this->log->getCreateRouteBodyEnemyKilledInvalidNpcId($guid->getId());
                        continue;
                    }

                    /** @var EnemyEngagedResultEvent $npcEngagedEvent */
                    $npcEngagedEvent = $npcEngagedEvents->get($guid->getGuid());

                    $npcEngagedEvents->forget($guid->getGuid());

                    $npcs->push(
                        new CreateRouteNpc(
                            $guid->getId(),
                            $guid->getSpawnUID(),
                            $npcEngagedEvent->getEngagedEvent()->getTimestamp()->format(CreateRouteBody::DATE_TIME_FORMAT),
                            $resultEvent->getBaseEvent()->getTimestamp()->format(CreateRouteBody::DATE_TIME_FORMAT),
                            new CreateRouteCoord(
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getPositionX(),
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getPositionY(),
                                $npcEngagedEvent->getEngagedEvent()->getAdvancedData()->getUiMapId()
                            )
                        )
                    );

                } elseif ($resultEvent instanceof SpellCast) {
                    /** @var Player $guid */
                    $advancedData = $resultEvent->getAdvancedCombatLogEvent()->getAdvancedData();

                    $spells->push(
                        new CreateRouteSpell(
                            $resultEvent->getSpellId(),
                            $advancedData->getInfoGuid()->getGuid(),
                            $resultEvent->getBaseEvent()->getTimestamp()->format(CreateRouteBody::DATE_TIME_FORMAT),
                            new CreateRouteCoord(
                                $advancedData->getPositionX(),
                                $advancedData->getPositionY(),
                                $advancedData->getUiMapId()
                            )
                        )
                    );
                }
            }

            if ($npcEngagedEvents->isNotEmpty()) {
                throw new Exception('Found enemies that weren\'t killed!');
            }

            return new CreateRouteBody(
                new CreateRouteSettings(true, true),
                $challengeMode,
                $npcs,
                $spells
            );

        } finally {
            $this->log->getCreateRouteBodyEnd();
        }
    }

    /**
     * @param MappingVersion    $mappingVersion
     * @param CreateRouteBody   $createRouteBody
     * @param DungeonRoute|null $dungeonRoute
     *
     * @return void
     */
    private function generateMapIcons(
        MappingVersion $mappingVersion,
        CreateRouteBody $createRouteBody,
        ?DungeonRoute $dungeonRoute = null
    ): void {
        $currentFloor      = null;
        $mapIconAttributes = collect();
        foreach ($createRouteBody->npcs as $npc) {
            $realUiMapId = Floor::UI_MAP_ID_MAPPING[$npc->coord->uiMapId] ?? $npc->coord->uiMapId;
            if ($currentFloor === null || $realUiMapId !== $currentFloor->ui_map_id) {
                $currentFloor = Floor::findByUiMapId($npc->coord->uiMapId);
            }

            $latLng = $currentFloor->calculateMapLocationForIngameLocation(
                $npc->coord->x,
                $npc->coord->y,
            );

            $comment = json_encode($npc);

            $mapIconAttributes->push([
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $currentFloor->id,
                'dungeon_route_id'   => optional($dungeonRoute)->id ?? null,
                'team_id'            => null,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DOT_YELLOW],
                'lat'                => $latLng['lat'],
                'lng'                => $latLng['lng'],
                'comment'            => $comment,
                'permanent_tooltip'  => 0,
            ]);
        }

        MapIcon::insert($mapIconAttributes->toArray());
    }
}
