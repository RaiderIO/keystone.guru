<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Http\Models\Request\CombatLog\Route\CombatLogRoute;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteCombatLogEventsBuilderLoggingInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @author Wouter
 *
 * @since 24/06/2023
 */
class CombatLogRouteCombatLogEventsBuilder extends CombatLogRouteDungeonRouteBuilder
{
    private CombatLogRouteCombatLogEventsBuilderLoggingInterface $log;

    public function __construct(
        SeasonServiceInterface                    $seasonService,
        CoordinatesServiceInterface               $coordinatesService,
        DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        DungeonRouteAffixGroupRepositoryInterface $dungeonRouteAffixGroupRepository,
        AffixGroupRepositoryInterface             $affixGroupRepository,
        KillZoneRepositoryInterface               $killZoneRepository,
        KillZoneEnemyRepositoryInterface          $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface          $killZoneSpellRepository,
        CombatLogRoute $combatLogRoute
    ) {
        /** @var CombatLogRouteCombatLogEventsBuilderLoggingInterface $log */
        $log       = App::make(CombatLogRouteCombatLogEventsBuilderLoggingInterface::class);
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
            $combatLogRoute
        );
    }

    public function getCombatLogEvents(): Collection
    {
        $result = collect();

        try {
            $this->log->getCombatLogEventsStart();

            $now   = Carbon::now();
            $start = Carbon::createFromFormat(CombatLogRoute::DATE_TIME_FORMAT, $this->combatLogRoute->challengeMode->start);
            $end   = Carbon::createFromFormat(CombatLogRoute::DATE_TIME_FORMAT, $this->combatLogRoute->challengeMode->end);

            $floors = $this->dungeonRoute->dungeon->floors->keyBy('id');

            foreach ($this->combatLogRoute->npcs as $npc) {
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
                    'run_id'            => $this->combatLogRoute->metadata->runId,
                    'challenge_mode_id' => $this->combatLogRoute->challengeMode->challengeModeId,
                    'level'             => $this->combatLogRoute->challengeMode->level,
                    'affix_ids'         => json_encode($this->combatLogRoute->challengeMode->affixes),
                    'success'           => $this->combatLogRoute->challengeMode->success,
                    'start'             => $start,
                    'end'               => $end,
                    'duration_ms'       => $this->combatLogRoute->challengeMode->durationMs,
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
