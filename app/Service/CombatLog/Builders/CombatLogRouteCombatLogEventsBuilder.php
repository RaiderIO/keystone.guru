<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Models\CombatLog\CombatLogEvent;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteCombatLogEventsBuilderLoggingInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * This class generates combat log events that we can insert in the combat log database, then to Opensearch,
 * which is then used for the local Service to have heat map data locally.
 *
 * @author Wouter
 *
 * @since 24/06/2023
 */
class CombatLogRouteCombatLogEventsBuilder extends CombatLogRouteCorrectionBuilder
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
        EnemyRepositoryInterface                  $enemyRepository,
        NpcRepositoryInterface                    $npcRepository,
        SpellRepositoryInterface                  $spellRepository,
        FloorRepositoryInterface                  $floorRepository,
        DungeonRepositoryInterface                $dungeonRepository,
        CombatLogRouteRequestModel                $combatLogRoute
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
            $enemyRepository,
            $npcRepository,
            $spellRepository,
            $floorRepository,
            $dungeonRepository,
            $combatLogRoute
        );
    }

    public function getCombatLogEvents(): Collection
    {
        $result = collect();

        try {
            $this->log->getCombatLogEventsStart($this->combatLogRoute->metadata->runId);

            $correctedCombatLogRoute = $this->getCombatLogRoute();

            $now   = Carbon::now();
            $start = Carbon::createFromFormat(CombatLogRouteRequestModel::DATE_TIME_FORMAT, $correctedCombatLogRoute->challengeMode->start);
            $end   = Carbon::createFromFormat(CombatLogRouteRequestModel::DATE_TIME_FORMAT, $correctedCombatLogRoute->challengeMode->end);

            foreach ($correctedCombatLogRoute->npcs as $npc) {
                $result->push(new CombatLogEvent(array_merge(
                    $this->getBaseCombatLogEventAttributes($correctedCombatLogRoute, $now, $start, $end, $npc->coord->uiMapId),
                    [
                        // Original event location
                        'pos_x'      => round($npc->coord->x, 2),
                        'pos_y'      => round($npc->coord->y, 2),
                        'pos_grid_x' => $npc->gridCoord->x,
                        'pos_grid_y' => $npc->gridCoord->y,
                        'event_type' => CombatLogEventEventType::NpcDeath->value,
                        'context'    => json_encode([
                            '@timestamp'       => $npc->getDiedAt(),
                            // Resolved enemy location
                            'pos_enemy_x'      => $npc->coordEnemy->x,
                            'pos_enemy_y'      => $npc->coordEnemy->y,
                            'pos_enemy_grid_x' => $npc->gridCoordEnemy->x,
                            'pos_enemy_grid_y' => $npc->gridCoordEnemy->y,
                        ]),
                    ]
                )));
            }

            foreach ($correctedCombatLogRoute->spells as $spell) {
                $result->push(new CombatLogEvent(array_merge(
                    $this->getBaseCombatLogEventAttributes($correctedCombatLogRoute, $now, $start, $end, $spell->coord->uiMapId),
                    [
                        // Original event location
                        'pos_x'      => round($spell->coord->x, 2),
                        'pos_y'      => round($spell->coord->y, 2),
                        'pos_grid_x' => $spell->gridCoord->x,
                        'pos_grid_y' => $spell->gridCoord->y,
                        'event_type' => CombatLogEventEventType::PlayerSpell->value,
                        'context'    => json_encode([
                            '@timestamp' => $spell->getCastAt(),
                            'spell_id'   => $spell->spellId,
                        ]),
                    ]
                )));
            }

            foreach ($correctedCombatLogRoute->playerDeaths ?? [] as $playerDeath) {
                $result->push(new CombatLogEvent(array_merge(
                    $this->getBaseCombatLogEventAttributes($correctedCombatLogRoute, $now, $start, $end, $playerDeath->coord->uiMapId),
                    [
                        // Original event location
                        'pos_x'      => round($playerDeath->coord->x, 2),
                        'pos_y'      => round($playerDeath->coord->y, 2),
                        'pos_grid_x' => $playerDeath->gridCoord->x,
                        'pos_grid_y' => $playerDeath->gridCoord->y,
                        'event_type' => CombatLogEventEventType::PlayerDeath->value,
                        'context'    => json_encode([
                            '@timestamp'   => $playerDeath->getDiedAt(),
                            'character_id' => $playerDeath->characterId,
                            'class_id'     => $playerDeath->classId,
                            'spec_id'      => $playerDeath->specId,
                            'item_level'   => $playerDeath->itemLevel,
                        ]),
                    ]
                )));
            }

        } finally {
            $this->log->getCombatLogEventsEnd();
        }

        return $result;
    }

    private function getBaseCombatLogEventAttributes(
        CombatLogRouteRequestModel $correctedCombatLogRoute,
        Carbon                     $now,
        Carbon                     $start,
        Carbon                     $end,
        int                        $uiMapId,
    ): array {
        return [
            'run_id'             => $correctedCombatLogRoute->metadata->runId,
            'keystone_run_id'    => $correctedCombatLogRoute->metadata->keystoneRunId,
            'logged_run_id'      => $correctedCombatLogRoute->metadata->loggedRunId,
            'period'             => $correctedCombatLogRoute->metadata->period,
            'season'             => $correctedCombatLogRoute->metadata->season,
            'region_id'          => $correctedCombatLogRoute->metadata->regionId,
            'realm_type'         => $correctedCombatLogRoute->metadata->realmType,
            'wow_instance_id'    => $correctedCombatLogRoute->metadata->wowInstanceId,
            'challenge_mode_id'  => $correctedCombatLogRoute->challengeMode->challengeModeId,
            'level'              => $correctedCombatLogRoute->challengeMode->level,
            'affix_ids'          => json_encode($correctedCombatLogRoute->challengeMode->affixes),
            'success'            => $correctedCombatLogRoute->challengeMode->success,
            'start'              => $start,
            'end'                => $end,
            'duration_ms'        => $correctedCombatLogRoute->challengeMode->durationMs,
            'par_time_ms'        => $correctedCombatLogRoute->challengeMode->parTimeMs,
            'timer_fraction'     => $correctedCombatLogRoute->challengeMode->timerFraction,
            'num_deaths'         => $correctedCombatLogRoute->challengeMode->numDeaths,
            'ui_map_id'          => $uiMapId,
            'num_members'        => $correctedCombatLogRoute->roster?->numMembers ?? 0,
            'average_item_level' => $correctedCombatLogRoute->roster?->averageItemLevel ?? 0,
            'characters'         => $this->getCharactersJsonFromRoster($correctedCombatLogRoute),
            'context'            => "[]",
            'created_at'         => $now,
            'updated_at'         => $now,
        ];
    }

    private function getCharactersJsonFromRoster(
        CombatLogRouteRequestModel $correctedCombatLogRoute
    ): string {
        $result = [];

        for ($i = 0; $i < $correctedCombatLogRoute->roster?->numMembers ?? 0; $i++) {
            $result[] = [
                'id'    => $correctedCombatLogRoute->roster->characterIds[$i] ?? 12345,
                'class' => $correctedCombatLogRoute->roster->classIds[$i] ?? 1, // Warrior
                'spec'  => $correctedCombatLogRoute->roster->specIds[$i] ?? 73, // Warrior Protection
            ];
        }

        return json_encode($result);
    }
}
