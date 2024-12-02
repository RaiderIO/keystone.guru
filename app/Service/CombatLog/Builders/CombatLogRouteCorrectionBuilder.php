<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Http\Models\Request\CombatLog\Route\CombatLogRoutePlayerDeathRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteChallengeModeRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteCoordRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteMetadataRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteNpcRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteNpcCorrectionRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRosterRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteSettingsRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteSpellRequestModel;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteCorrectionBuilderLoggingInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

/**
 * Takes a CombatLogRouteRequestModel and pushes it through ARC. It then returns a new CombatLogRouteRequestModel with the locations corrected
 * to those of resolved enemies.
 *
 * @author Wouter
 *
 * @since 23/06/2024
 */
class CombatLogRouteCorrectionBuilder extends CombatLogRouteDungeonRouteBuilder
{
    private CombatLogRouteCorrectionBuilderLoggingInterface $log;

    public function __construct(
        SeasonServiceInterface                    $seasonService,
        CoordinatesServiceInterface               $coordinatesService,
        DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        DungeonRouteAffixGroupRepositoryInterface $dungeonRouteAffixGroupRepository,
        AffixGroupRepositoryInterface             $affixGroupRepository,
        KillZoneRepositoryInterface               $killZoneRepository,
        KillZoneEnemyRepositoryInterface          $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface          $killZoneSpellRepository,
        CombatLogRouteRequestModel $combatLogRoute
    ) {
        /** @var CombatLogRouteCorrectionBuilderLoggingInterface $log */
        $log       = App::make(CombatLogRouteCorrectionBuilderLoggingInterface::class);
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

    public function getCombatLogRoute(): CombatLogRouteRequestModel
    {
        /** @var Collection<CombatLogRouteNpcRequestModel> $npcs */
        $npcs = new Collection();
        /** @var Collection<CombatLogRouteSpellRequestModel> $npcs */
        $spells = new Collection();
        /** @var Collection<CombatLogRoutePlayerDeathRequestModel> $playerDeaths */
        $playerDeaths = new Collection();

        try {
            $this->log->getCombatLogRouteStart();

            $floors = $this->dungeonRoute->dungeon->floors->keyBy('id');

            foreach ($this->combatLogRoute->npcs as $npc) {
                $resolvedEnemy = $npc->getResolvedEnemy();

                if ($resolvedEnemy === null) {
                    $this->log->getCombatLogRouteEnemyCouldNotBeResolved($npc->npcId, $npc->spawnUid);
                    // If we couldn't resolve the enemy, stop
                    continue;
                }

                /** @var Floor $floor */
                $floor = $floors->get($resolvedEnemy->floor_id);
                $resolvedEnemy->setRelation('floor', $floor);

                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                    $resolvedEnemy->getLatLng()
                );

                $npcs->push(
                    new CombatLogRouteNpcCorrectionRequestModel(
                        $npc->npcId,
                        $npc->spawnUid,
                        $npc->engagedAt,
                        $npc->diedAt,
                        new CombatLogRouteCoordRequestModel(
                            $npc->coord->x,
                            $npc->coord->y,
                            $npc->coord->uiMapId
                        ),
                        new CombatLogRouteCoordRequestModel(
                            $ingameXY->getX(2),
                            $ingameXY->getY(2),
                            $floor->ui_map_id
                        )
                    )
                );
            }

            foreach ($this->combatLogRoute->spells as $spell) {
                $spells->push(
                    new CombatLogRouteSpellRequestModel(
                        $spell->spellId,
                        $spell->playerUid,
                        $spell->castAt,
                        $spell->coord,
                    )
                );
            }

            foreach ($this->combatLogRoute->playerDeaths ?? [] as $playerDeath) {
                $playerDeaths->push(
                    new CombatLogRoutePlayerDeathRequestModel(
                        $playerDeath->characterId,
                        $playerDeath->classId,
                        $playerDeath->specId,
                        $playerDeath->itemLevel,
                        $playerDeath->diedAt,
                        $playerDeath->coord,
                    )
                );
            }

            $result = new CombatLogRouteRequestModel(
            // For now no changes in these, but making copies regardless
                new CombatLogRouteMetadataRequestModel(
                    $this->combatLogRoute->metadata->runId,
                    $this->combatLogRoute->metadata->keystoneRunId,
                    $this->combatLogRoute->metadata->loggedRunId,
                    $this->combatLogRoute->metadata->period,
                    $this->combatLogRoute->metadata->season,
                    $this->combatLogRoute->metadata->regionId,
                    $this->combatLogRoute->metadata->realmType,
                    $this->combatLogRoute->metadata->wowInstanceId,
                ),
                new CombatLogRouteSettingsRequestModel(
                    $this->combatLogRoute->settings->temporary,
                    $this->combatLogRoute->settings->debugIcons
                ),
                new CombatLogRouteChallengeModeRequestModel(
                    $this->combatLogRoute->challengeMode->start,
                    $this->combatLogRoute->challengeMode->end,
                    $this->combatLogRoute->challengeMode->success,
                    $this->combatLogRoute->challengeMode->durationMs,
                    $this->combatLogRoute->challengeMode->parTimeMs,
                    $this->combatLogRoute->challengeMode->timerFraction,
                    $this->combatLogRoute->challengeMode->challengeModeId,
                    $this->combatLogRoute->challengeMode->level,
                    $this->combatLogRoute->challengeMode->numDeaths,
                    $this->combatLogRoute->challengeMode->affixes,
                ),
                new CombatLogRouteRosterRequestModel(
                    $this->combatLogRoute->roster?->numMembers,
                    $this->combatLogRoute->roster?->averageItemLevel,
                    $this->combatLogRoute->roster?->characterIds,
                    $this->combatLogRoute->roster?->specIds,
                    $this->combatLogRoute->roster?->classIds,
                ),
                $npcs,
                $spells,
                $playerDeaths
            );
        } finally {
            $this->log->getCombatLogRouteEnd();
        }

        return $result;
    }
}
