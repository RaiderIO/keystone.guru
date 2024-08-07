<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Builders\Logging\CreateRouteBodyCombatLogEventsBuilderLoggingInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @author Wouter
 *
 * @since 24/06/2023
 */
class CreateRouteBodyCombatLogEventsBuilder extends CreateRouteBodyDungeonRouteBuilder
{
    private CreateRouteBodyCombatLogEventsBuilderLoggingInterface $log;

    public function __construct(
        SeasonServiceInterface                    $seasonService,
        CoordinatesServiceInterface               $coordinatesService,
        DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        DungeonRouteAffixGroupRepositoryInterface $dungeonRouteAffixGroupRepository,
        AffixGroupRepositoryInterface             $affixGroupRepository,
        KillZoneRepositoryInterface               $killZoneRepository,
        KillZoneEnemyRepositoryInterface          $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface          $killZoneSpellRepository,
        CreateRouteBody                           $createRouteBody
    ) {
        /** @var CreateRouteBodyCombatLogEventsBuilderLoggingInterface $log */
        $log       = App::make(CreateRouteBodyCombatLogEventsBuilderLoggingInterface::class);
        $this->log = $log;

        parent::__construct(
            $seasonService,
            $coordinatesService,
            $dungeonRouteRepository,
            $dungeonRouteAffixGroupRepository,
            $affixGroupRepository,
            $killZoneRepository,
            $killZoneEnemyRepository,
            $killZoneSpellRepository,
            $createRouteBody
        );
    }

    public function getCombatLogEvents(): Collection
    {
        $result = collect();

        try {
            $this->log->getCombatLogEventsStart();

            $now   = Carbon::now();
            $start = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $this->createRouteBody->challengeMode->start);
            $end   = Carbon::createFromFormat(CreateRouteBody::DATE_TIME_FORMAT, $this->createRouteBody->challengeMode->end);

            $floors = $this->dungeonRoute->dungeon->floors->keyBy('id');

            foreach ($this->createRouteBody->npcs as $npc) {
                $resolvedEnemy = $npc->getResolvedEnemy();

                if ($resolvedEnemy === null) {
                    $this->log->getCombatLogEventsEnemyCouldNotBeResolved($npc->npcId, $npc->spawnUid);
                    // If we couldn't resolve the enemy, stop
                    continue;
                }

                /** @var Floor $floor */
                $floor = $floors->get($resolvedEnemy->floor_id);
                $resolvedEnemy->setRelation('floor', $floor);

                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($resolvedEnemy->getLatLng());

                $result->push(new CombatLogEvent([
                    'run_id'            => $this->createRouteBody->metadata->runId,
                    'challenge_mode_id' => $this->createRouteBody->challengeMode->challengeModeId,
                    'level'             => $this->createRouteBody->challengeMode->level,
                    'affix_ids'         => json_encode($this->createRouteBody->challengeMode->affixes),
                    'success'           => $this->createRouteBody->challengeMode->success,
                    'start'             => $start,
                    'end'               => $end,
                    'duration_ms'       => $this->createRouteBody->challengeMode->durationMs,
                    'ui_map_id'         => $floor->ui_map_id,
                    // Original event location
                    'pos_x'             => round($npc->coord->x, 2),
                    'pos_y'             => round($npc->coord->y, 2),
                    // Resolved enemy location
                    'pos_enemy_x'       => $ingameXY->getX(2),
                    'pos_enemy_y'       => $ingameXY->getY(2),
                    'event_type'        => CombatLogEvent::EVENT_TYPE_ENEMY_KILLED,
                    'characters'        => "[]",
                    'context'           => "[]",
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]));
            }

        } finally {
            $this->log->getCombatLogEventsEnd();
        }

        return $result;
    }
}
