<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Http\Models\Request\CombatLog\Route\CombatLogRoutePlayerDeathRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteSpellRequestModel;
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
            $combatLogRoute
        );
    }

    public function getCombatLogEvents(): Collection
    {
        $result = collect();

        try {
            $this->log->getCombatLogEventsStart($this->combatLogRoute->metadata->runId);

            $now   = Carbon::now();
            $start = Carbon::createFromFormat(CombatLogRouteRequestModel::DATE_TIME_FORMAT, $this->combatLogRoute->challengeMode->start);
            $end   = Carbon::createFromFormat(CombatLogRouteRequestModel::DATE_TIME_FORMAT, $this->combatLogRoute->challengeMode->end);

            $floorsById      = $this->dungeonRoute->dungeon->floors->keyBy('id');
            $floorsByUiMapId = $this->dungeonRoute->dungeon->floors->keyBy('ui_map_id');

            foreach ($this->combatLogRoute->npcs as $npc) {
                $resolvedEnemy = $npc->getResolvedEnemy();

                if ($resolvedEnemy === null) {
                    $this->log->getCombatLogEventsEnemyCouldNotBeResolved($npc->npcId, $npc->spawnUid);
                    // If we couldn't resolve the enemy, stop
                    continue;
                }

                /** @var Floor $floor */
                $floor = $floorsById->get($resolvedEnemy->floor_id);
                $resolvedEnemy->setRelation('floor', $floor);

                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($resolvedEnemy->getLatLng());

                $result->push(new CombatLogEvent(array_merge(
                    $this->getBaseCombatLogEventAttributes($now, $start, $end, $floor),
                    [
                        // Original event location
                        'pos_x'      => round($npc->coord->x, 2),
                        'pos_y'      => round($npc->coord->y, 2),
                        'event_type' => CombatLogEvent::EVENT_TYPE_ENEMY_KILLED,
                        'context'    => json_encode([
                            '@timestamp'  => $npc->getDiedAt(),
                            // Resolved enemy location
                            'pos_enemy_x' => $ingameXY->getX(2),
                            'pos_enemy_y' => $ingameXY->getY(2),
                        ]),
                    ]
                )));
            }

            foreach ($this->combatLogRoute->spells as $spell) {
                /** @var CombatLogRouteSpellRequestModel $spell */
                $floor = $floorsByUiMapId->get(Floor::UI_MAP_ID_MAPPING[$spell->coord->uiMapId] ?? $spell->coord->uiMapId);

                if ($floor === null) {
                    $this->log->getCombatLogEventsSpellFloorCouldNotBeResolved($spell->coord->uiMapId);
                    continue;
                }

                $result->push(new CombatLogEvent(array_merge(
                    $this->getBaseCombatLogEventAttributes($now, $start, $end, $floor),
                    [
                        // Original event location
                        'pos_x'      => round($spell->coord->x, 2),
                        'pos_y'      => round($spell->coord->y, 2),
                        'event_type' => CombatLogEvent::EVENT_TYPE_SPELL_CAST,
                        'context'    => json_encode([
                            '@timestamp' => $spell->getCastAt(),
                            'spell_id'   => $spell->spellId,
                        ]),
                    ]
                )));
            }

            foreach ($this->combatLogRoute->playerDeaths as $playerDeath) {
                /** @var CombatLogRoutePlayerDeathRequestModel $playerDeath */
                $floor = $floorsByUiMapId->get(Floor::UI_MAP_ID_MAPPING[$playerDeath->coord->uiMapId] ?? $playerDeath->coord->uiMapId);

                if ($floor === null) {
                    $this->log->getCombatLogEventsPlayerDeathFloorCouldNotBeResolved($playerDeath->coord->uiMapId);
                    continue;
                }

                $result->push(new CombatLogEvent(array_merge(
                    $this->getBaseCombatLogEventAttributes($now, $start, $end, $floor),
                    [
                        // Original event location
                        'pos_x'      => round($playerDeath->coord->x, 2),
                        'pos_y'      => round($playerDeath->coord->y, 2),
                        'event_type' => CombatLogEvent::EVENT_TYPE_PLAYER_DEATH,
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
        Carbon $now,
        Carbon $start,
        Carbon $end,
        Floor  $floor,
    ): array {
        return [
            'run_id'             => $this->combatLogRoute->metadata->runId,
            'keystone_run_id'    => $this->combatLogRoute->metadata->keystoneRunId,
            'logged_run_id'      => $this->combatLogRoute->metadata->loggedRunId,
            'period'             => $this->combatLogRoute->metadata->period,
            'season'             => $this->combatLogRoute->metadata->season,
            'region_id'          => $this->combatLogRoute->metadata->regionId,
            'realm_type'         => $this->combatLogRoute->metadata->realmType,
            'wow_instance_id'    => $this->combatLogRoute->metadata->wowInstanceId,
            'challenge_mode_id'  => $this->combatLogRoute->challengeMode->challengeModeId,
            'level'              => $this->combatLogRoute->challengeMode->level,
            'affix_ids'          => json_encode($this->combatLogRoute->challengeMode->affixes),
            'success'            => $this->combatLogRoute->challengeMode->success,
            'start'              => $start,
            'end'                => $end,
            'duration_ms'        => $this->combatLogRoute->challengeMode->durationMs,
            'par_time_ms'        => $this->combatLogRoute->challengeMode->parTimeMs,
            'timer_fraction'     => $this->combatLogRoute->challengeMode->timerFraction,
            'num_deaths'         => $this->combatLogRoute->challengeMode->numDeaths,
            'ui_map_id'          => $floor->ui_map_id,
            'num_members'        => $this->combatLogRoute->roster->numMembers,
            'average_item_level' => $this->combatLogRoute->roster->averageItemLevel,
            'characters'         => $this->getCharactersJsonFromRoster(),
            'context'            => "[]",
            'created_at'         => $now,
            'updated_at'         => $now,
        ];
    }

    private function getCharactersJsonFromRoster(): string
    {
        $result = [];

        for ($i = 0; $i < $this->combatLogRoute->roster->numMembers; $i++) {
            $result[] = [
                'id'    => $this->combatLogRoute->roster->characterIds[$i],
                'class' => $this->combatLogRoute->roster->classIds[$i],
                'spec'  => $this->combatLogRoute->roster->specIds[$i],
            ];
        }

        return json_encode($result);
    }
}
