<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Floor\Floor;
use App\Repositories\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\KillZone\KillZoneRepositoryInterface;
use App\Repositories\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Logging\CreateRouteBodyCombatLogEventsBuilderLoggingInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @author Wouter
 *
 * @since 24/06/2023
 */
class CreateRouteBodyCombatLogEventsBuilder extends CreateRouteBodyDungeonRouteBuilder
{
    public function __construct(
        SeasonServiceInterface           $seasonService,
        CoordinatesServiceInterface      $coordinatesService,
        DungeonRouteRepositoryInterface  $dungeonRouteRepository,
        KillZoneRepositoryInterface      $killZoneRepository,
        KillZoneEnemyRepositoryInterface $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface $killZoneSpellRepository,
        CreateRouteBody                  $createRouteBody
    ) {
        parent::__construct(
            $seasonService,
            $coordinatesService,
            $dungeonRouteRepository,
            $killZoneRepository,
            $killZoneEnemyRepository,
            $killZoneSpellRepository,
            $createRouteBody
        );

        /** @var CreateRouteBodyCombatLogEventsBuilderLoggingInterface $log */
        $log       = App::make(CreateRouteBodyCombatLogEventsBuilderLoggingInterface::class);
        $this->log = $log;
    }

    public function getCombatLogEvents(): Collection
    {
        $result = collect();

        try {
            $this->log->getCombatLogEventsStart();

            $start      = Carbon::parse(CreateRouteBody::DATE_TIME_FORMAT, $this->createRouteBody->challengeMode->start);
            $end        = Carbon::parse(CreateRouteBody::DATE_TIME_FORMAT, $this->createRouteBody->challengeMode->end);
            $durationMS = (int)($end->diff($start)->f * 1000);

            $floors = $this->dungeonRoute->dungeon->floors->keyBy('id');

            foreach ($this->dungeonRoute->killZones as $killZone) {
                foreach ($killZone->getEnemies(true) as $enemy) {
                    /** @var Floor $floor */
                    $floor = $floors->get($enemy->floor_id);

                    $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($enemy->getLatLng());

                    $result->push(new CombatLogEvent([
                        'run_id'            => $this->createRouteBody->metadata->runId,
                        'challenge_mode_id' => $this->createRouteBody->challengeMode->challengeModeId,
                        'level'             => $this->dungeonRoute->level_min,
                        'affix_ids'         => $this->createRouteBody->challengeMode->affixes,
                        'success'           => $this->createRouteBody->challengeMode->success,
                        'start'             => $start->getTimestamp(),
                        'end'               => $end->getTimestamp(),
                        'duration_ms'       => $durationMS,
                        'ui_map_id'         => $floor->ui_map_id,
                        'pos_x'             => $ingameXY->getX(),
                        'pos_y'             => $ingameXY->getY(),
                        'event_type'        => App\Models\CombatLog\CombatLogEvent::EVENT_TYPE_ENEMY_KILLED,
                        'characters'        => [],
                        'context'           => [],
                    ]));
                }
            }

        } finally {
            $this->log->getCombatLogEventsEnd();
        }

        return $result;
    }
}
