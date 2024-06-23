<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Logging\CreateRouteBodyCombatLogEventsBuilderLoggingInterface;
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

            $floors  = $this->dungeonRoute->dungeon->floors->keyBy('id');
            $enemies = $this->dungeonRoute->mappingVersion->enemies->keyBy(function (Enemy $enemy) {
                return sprintf('%d-%d', $enemy->npc_id, $enemy->mdt_id);
            });

            foreach ($this->dungeonRoute->killZones as $killZone) {
                foreach ($killZone->killZoneEnemies as $killZoneEnemy) {
                    /** @var Enemy $enemy */
                    $enemy = $enemies->get(sprintf('%d-%d', $killZoneEnemy->npc_id, $killZoneEnemy->mdt_id));
                    if ($enemy === null) {
                        $this->log->getCombatLogEventsEnemyNotFound($enemy->npc_id, $enemy->mdt_id);
                        continue;
                    }

                    /** @var Floor $floor */
                    $floor = $floors->get($enemy->floor_id);

                    // Lazy loading
                    $enemy->setRelation('floor', $floor);

                    $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($enemy->getLatLng());

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
                        'pos_x'             => $ingameXY->getX(),
                        'pos_y'             => $ingameXY->getY(),
                        'event_type'        => CombatLogEvent::EVENT_TYPE_ENEMY_KILLED,
                        'characters'        => "[]",
                        'context'           => "[]",
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]));
                }
            }

        } finally {
            $this->log->getCombatLogEventsEnd();
        }

        return $result;
    }
}
