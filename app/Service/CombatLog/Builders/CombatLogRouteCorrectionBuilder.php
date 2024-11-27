<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Http\Models\Request\CombatLog\Route\CombatLogRoute;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteChallengeMode;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteCoord;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteMetadata;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteNpc;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteNpcCorrection;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteSettings;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteSpell;
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
 * Takes a CombatLogRoute and pushes it through ARC. It then returns a new CombatLogRoute with the locations corrected
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
        CombatLogRoute $combatLogRoute
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

    public function getCombatLogRoute(): CombatLogRoute
    {
        /** @var Collection<CombatLogRouteNpc> $npcs */
        $npcs = new Collection();
        /** @var Collection<CombatLogRouteSpell> $npcs */
        $spells = new Collection();

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
                    new CombatLogRouteNpcCorrection(
                        $npc->npcId,
                        $npc->spawnUid,
                        $npc->engagedAt,
                        $npc->diedAt,
                        new CombatLogRouteCoord(
                            $npc->coord->x,
                            $npc->coord->y,
                            $npc->coord->uiMapId
                        ),
                        new CombatLogRouteCoord(
                            $ingameXY->getX(2),
                            $ingameXY->getY(2),
                            $floor->ui_map_id
                        )
                    )
                );
            }

            foreach ($this->combatLogRoute->spells as $spell) {
                $spells->push(
                    new CombatLogRouteSpell(
                        $spell->spellId,
                        $spell->playerUid,
                        $spell->castAt,
                        $spell->coord,
                    )
                );
            }

            $result = new CombatLogRoute(
            // For now no changes in these, but making copies regardless
                new CombatLogRouteMetadata($this->combatLogRoute->metadata->runId),
                new CombatLogRouteSettings($this->combatLogRoute->settings->temporary, $this->combatLogRoute->settings->debugIcons),
                new CombatLogRouteChallengeMode(
                    $this->combatLogRoute->challengeMode->start,
                    $this->combatLogRoute->challengeMode->end,
                    $this->combatLogRoute->challengeMode->success,
                    $this->combatLogRoute->challengeMode->durationMs,
                    $this->combatLogRoute->challengeMode->challengeModeId,
                    $this->combatLogRoute->challengeMode->level,
                    $this->combatLogRoute->challengeMode->affixes,
                ),
                $npcs,
                $spells
            );
        } finally {
            $this->log->getCombatLogRouteEnd();
        }

        return $result;
    }
}
