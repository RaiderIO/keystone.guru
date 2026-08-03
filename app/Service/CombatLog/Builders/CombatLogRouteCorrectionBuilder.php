<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Dto\Request\CombatLog\Route\CombatLogRouteChallengeModeRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteCoordRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteCorrectionRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteMetadataRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcCorrectionRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRoutePlayerDeathCorrectionRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRoutePlayerDeathRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRosterRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteSettingsRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteSpellCorrectionRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteSpellRequestDto;
use App\Logic\Structs\IngameXY;
use App\Models\Floor\Floor;
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
use App\Service\CombatLog\Builders\Logging\CombatLogRouteCorrectionBuilderLoggingInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use Override;

/**
 * Takes a CombatLogRouteRequestDto and pushes it through ARC. It then returns a new CombatLogRouteRequestDto with the locations corrected
 * to those of resolved enemies.
 *
 * @author Wouter
 *
 * @since 23/06/2024
 */
class CombatLogRouteCorrectionBuilder extends CombatLogRouteDungeonRouteBuilder
{
    private readonly CombatLogRouteCorrectionBuilderLoggingInterface $log;

    public function __construct(
        SeasonServiceInterface                    $seasonService,
        SeasonAffixGroupServiceInterface          $seasonAffixGroupService,
        CoordinatesServiceInterface               $coordinatesService,
        DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        DungeonRouteAffixGroupRepositoryInterface $dungeonRouteAffixGroupRepository,
        KillZoneRepositoryInterface               $killZoneRepository,
        KillZoneEnemyRepositoryInterface          $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface          $killZoneSpellRepository,
        EnemyRepositoryInterface                  $enemyRepository,
        NpcRepositoryInterface                    $npcRepository,
        SpellRepositoryInterface                  $spellRepository,
        FloorRepositoryInterface                  $floorRepository,
        DungeonRepositoryInterface                $dungeonRepository,
        CombatLogRouteRequestDto                  $combatLogRoute,
    ) {
        /** @var CombatLogRouteCorrectionBuilderLoggingInterface $log */
        $log       = App::make(CombatLogRouteCorrectionBuilderLoggingInterface::class);
        $this->log = $log;

        parent::__construct(
            $seasonService,
            $seasonAffixGroupService,
            $coordinatesService,
            $dungeonRouteRepository,
            $dungeonRouteAffixGroupRepository,
            $killZoneRepository,
            $killZoneEnemyRepository,
            $killZoneSpellRepository,
            $enemyRepository,
            $npcRepository,
            $spellRepository,
            $floorRepository,
            $dungeonRepository,
            $combatLogRoute,
        );
    }

    #[Override]
    protected function buildFinished(): void
    {
        // Do not call parent - we don't care about enemy forces etc
        $this->dungeonRoute->setRelation('killZones', $this->killZones);
    }

    public function getCombatLogRoute(): CombatLogRouteCorrectionRequestDto
    {
        /** @var Collection<int, CombatLogRouteNpcRequestDto> $npcs */
        $npcs = new Collection();
        /** @var Collection<int, CombatLogRouteSpellRequestDto> $spells */
        $spells = new Collection();
        /** @var Collection<int, CombatLogRoutePlayerDeathRequestDto> $playerDeaths */
        $playerDeaths = new Collection();

        try {
            $this->log->getCombatLogRouteStart();

            $floorsById = $this->dungeonRoute->dungeon->floors->keyBy('id');

            // Keep track of when we switch to the shadow realm in Darkflame Cleft for additional corrections
            $darkflameCleftShadowRealmSwitchTime = null;

            foreach ($this->combatLogRoute->npcs as $npc) {
                $resolvedEnemy = $npc->getResolvedEnemy();

                if ($resolvedEnemy === null) {
                    $this->log->getCombatLogRouteEnemyCouldNotBeResolved($npc->npcId, $npc->spawnUid);
                    // If we couldn't resolve the enemy, stop
                    continue;
                }

                /** @var Floor $resolvedEnemyFloor */
                $resolvedEnemyFloor = $floorsById->get($resolvedEnemy->floor_id);
                $resolvedEnemy->setRelation('floor', $resolvedEnemyFloor);

                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                    $resolvedEnemy->getLatLng(),
                );

                // Catch the time at which we should switch floors to the Shadow Realm, so we can perform proper
                // corrections for spells and deaths that happen in the Shadow Realm
                if ($npc->coord->uiMapId === Floor::DARKFLAME_CLEFT_SHADOW_REALM_UI_MAP_ID) {
                    if ($darkflameCleftShadowRealmSwitchTime === null ||
                        $darkflameCleftShadowRealmSwitchTime->isAfter($npc->getEngagedAt())) {
                        $darkflameCleftShadowRealmSwitchTime = $npc->getEngagedAt();
                    }
                }

                $gridLocation = $this->coordinatesService->calculateGridLocationForIngameLocation(
                    new IngameXY(
                        $npc->coord->x,
                        $npc->coord->y,
                        // Fallback on the enemy's floor just in case
                        $this->floorRepository->findByUiMapId($npc->coord->uiMapId) ?? $resolvedEnemy->floor,
                    ),
                    config('keystoneguru.heatmap.service.data.player.size_x'),
                    config('keystoneguru.heatmap.service.data.player.size_y'),
                );
                $gridLocationEnemy = $this->coordinatesService->calculateGridLocationForIngameLocation(
                    $ingameXY,
                    config('keystoneguru.heatmap.service.data.enemy.size_x'),
                    config('keystoneguru.heatmap.service.data.enemy.size_y'),
                );

                $npcs->push(
                    new CombatLogRouteNpcCorrectionRequestDto(
                        $npc->npcId,
                        $npc->spawnUid,
                        $npc->engagedAt,
                        $npc->diedAt,
                        new CombatLogRouteCoordRequestDto(
                            $npc->coord->x,
                            $npc->coord->y,
                            $npc->coord->uiMapId,
                        ),
                        new CombatLogRouteCoordRequestDto(
                            $ingameXY->getX(2),
                            $ingameXY->getY(2),
                            $resolvedEnemyFloor->ui_map_id,
                        ),
                        new CombatLogRouteCoordRequestDto(
                            $gridLocation->getX(2),
                            $gridLocation->getY(2),
                            $npc->coord->uiMapId,
                        ),
                        new CombatLogRouteCoordRequestDto(
                            $gridLocationEnemy->getX(2),
                            $gridLocationEnemy->getY(2),
                            $resolvedEnemyFloor->ui_map_id,
                        ),
                    ),
                );
            }

            foreach ($this->combatLogRoute->spells as $spell) {
                $floor = $this->floorRepository->findByUiMapId($spell->coord->uiMapId);
                if ($floor === null) {
                    if (!Floor::isUiMapIdOpenWorld($spell->coord->uiMapId)) {
                        $this->log->getCombatLogRouteSpellFloorNotFound($spell->coord->uiMapId);
                    }
                    continue;
                }

                if ($darkflameCleftShadowRealmSwitchTime !== null &&
                    $spell->getCastAt()->isAfter($darkflameCleftShadowRealmSwitchTime)) {
                    $floor                 = $this->floorRepository->findByUiMapId(Floor::DARKFLAME_CLEFT_SHADOW_REALM_UI_MAP_ID);
                    $spell->coord->uiMapId = Floor::DARKFLAME_CLEFT_SHADOW_REALM_UI_MAP_ID;
                }

                $gridLocation = $this->coordinatesService->calculateGridLocationForIngameLocation(
                    new IngameXY(
                        $spell->coord->x,
                        $spell->coord->y,
                        $floor,
                    ),
                    config('keystoneguru.heatmap.service.data.player.size_x'),
                    config('keystoneguru.heatmap.service.data.player.size_y'),
                );

                $spells->push(
                    new CombatLogRouteSpellCorrectionRequestDto(
                        $spell->spellId,
                        $spell->playerUid,
                        $spell->castAt,
                        $spell->coord,
                        new CombatLogRouteCoordRequestDto(
                            $gridLocation->getX(2),
                            $gridLocation->getY(2),
                            $spell->coord->uiMapId,
                        ),
                    ),
                );
            }

            foreach ($this->combatLogRoute->playerDeaths ?? [] as $playerDeath) {
                $floor = $this->floorRepository->findByUiMapId($playerDeath->coord->uiMapId);
                if ($floor === null) {
                    if (!Floor::isUiMapIdOpenWorld($playerDeath->coord->uiMapId)) {
                        $this->log->getCombatLogRoutePlayerDeathFloorNotFound($playerDeath->coord->uiMapId);
                    }
                    continue;
                }

                if ($darkflameCleftShadowRealmSwitchTime !== null &&
                    $playerDeath->getDiedAt()->isAfter($darkflameCleftShadowRealmSwitchTime)) {
                    $floor                       = $this->floorRepository->findByUiMapId(Floor::DARKFLAME_CLEFT_SHADOW_REALM_UI_MAP_ID);
                    $playerDeath->coord->uiMapId = Floor::DARKFLAME_CLEFT_SHADOW_REALM_UI_MAP_ID;
                }

                $gridLocation = $this->coordinatesService->calculateGridLocationForIngameLocation(
                    new IngameXY(
                        $playerDeath->coord->x,
                        $playerDeath->coord->y,
                        $floor,
                    ),
                    config('keystoneguru.heatmap.service.data.player.size_x'),
                    config('keystoneguru.heatmap.service.data.player.size_y'),
                );

                $playerDeaths->push(
                    new CombatLogRoutePlayerDeathCorrectionRequestDto(
                        $playerDeath->characterId,
                        $playerDeath->classId,
                        $playerDeath->specId,
                        $playerDeath->itemLevel,
                        $playerDeath->diedAt,
                        $playerDeath->coord,
                        new CombatLogRouteCoordRequestDto(
                            $gridLocation->getX(2),
                            $gridLocation->getY(2),
                            $playerDeath->coord->uiMapId,
                        ),
                    ),
                );
            }

            $result = new CombatLogRouteCorrectionRequestDto(
                // For now no changes in these, but making copies regardless
                new CombatLogRouteMetadataRequestDto(
                    $this->combatLogRoute->metadata->runId,
                    $this->combatLogRoute->metadata->keystoneRunId,
                    $this->combatLogRoute->metadata->loggedRunId,
                    $this->combatLogRoute->metadata->period,
                    $this->combatLogRoute->metadata->season,
                    $this->combatLogRoute->metadata->regionId,
                    $this->combatLogRoute->metadata->realmType,
                    $this->combatLogRoute->metadata->wowInstanceId,
                ),
                new CombatLogRouteSettingsRequestDto(
                    $this->combatLogRoute->settings->temporary,
                    $this->combatLogRoute->settings->debugIcons,
                    $this->combatLogRoute->settings->mappingVersion,
                ),
                new CombatLogRouteChallengeModeRequestDto(
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
                new CombatLogRouteRosterRequestDto(
                    $this->combatLogRoute->roster?->numMembers,
                    $this->combatLogRoute->roster?->averageItemLevel,
                    $this->combatLogRoute->roster?->characterIds,
                    $this->combatLogRoute->roster?->specIds,
                    $this->combatLogRoute->roster?->classIds,
                ),
                $npcs,
                $spells,
                $playerDeaths,
            );
        } finally {
            $this->log->getCombatLogRouteEnd();
        }

        return $result;
    }
}
