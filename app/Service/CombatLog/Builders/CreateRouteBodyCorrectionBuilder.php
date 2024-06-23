<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Logging\CreateRouteBodyCorrectionBuilderLoggingInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteChallengeMode;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteCoord;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteMetadata;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteNpc;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteNpcCorrection;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteSettings;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteSpell;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

/**
 * Takes a CreateRouteBody and pushes it through ARC. It then returns a new CreateRouteBody with the locations corrected
 * to those of resolved enemies.
 *
 * @author Wouter
 *
 * @since 23/06/2024
 */
class CreateRouteBodyCorrectionBuilder extends CreateRouteBodyDungeonRouteBuilder
{
    private CreateRouteBodyCorrectionBuilderLoggingInterface $log;

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
        /** @var CreateRouteBodyCorrectionBuilderLoggingInterface $log */
        $log       = App::make(CreateRouteBodyCorrectionBuilderLoggingInterface::class);
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

    public function getCreateRouteBody(): CreateRouteBody
    {
        /** @var Collection<CreateRouteNpc> $npcs */
        $npcs = new Collection();
        /** @var Collection<CreateRouteSpell> $npcs */
        $spells = new Collection();

        try {
            $this->log->getCreateRouteBodyStart();

            $floors  = $this->dungeonRoute->dungeon->floors->keyBy('id');

            foreach ($this->createRouteBody->npcs as $npc) {
                $resolvedEnemy = $npc->getResolvedEnemy();

                if ($resolvedEnemy === null) {
                    $this->log->getCreateRouteBodyEnemyCouldNotBeResolved($npc->npcId, $npc->spawnUid);
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
                    new CreateRouteNpcCorrection(
                        $npc->npcId,
                        $npc->spawnUid,
                        $npc->engagedAt,
                        $npc->diedAt,
                        new CreateRouteCoord(
                            $npc->coord->x,
                            $npc->coord->y,
                            $npc->coord->uiMapId
                        ),
                        new CreateRouteCoord(
                            $ingameXY->getX(2),
                            $ingameXY->getY(2),
                            $floor->ui_map_id
                        )
                    )
                );
            }

            foreach ($this->createRouteBody->spells as $spell) {
                $spells->push(
                    new CreateRouteSpell(
                        $spell->spellId,
                        $spell->playerUid,
                        $spell->castAt,
                        $spell->coord,
                    )
                );
            }

            $result = new CreateRouteBody(
            // For now no changes in these, but making copies regardless
                new CreateRouteMetadata($this->createRouteBody->metadata->runId),
                new CreateRouteSettings($this->createRouteBody->settings->temporary, $this->createRouteBody->settings->debugIcons),
                new CreateRouteChallengeMode(
                    $this->createRouteBody->challengeMode->start,
                    $this->createRouteBody->challengeMode->end,
                    $this->createRouteBody->challengeMode->success,
                    $this->createRouteBody->challengeMode->durationMs,
                    $this->createRouteBody->challengeMode->challengeModeId,
                    $this->createRouteBody->challengeMode->level,
                    $this->createRouteBody->challengeMode->affixes,
                ),
                $npcs,
                $spells
            );
        } finally {
            $this->log->getCreateRouteBodyEnd();
        }

        return $result;
    }
}
