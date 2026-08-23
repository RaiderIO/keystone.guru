<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;

/**
 * Keeps the enemies of a floor the party has already left behind from competing for kills on the floor above.
 *
 * Applies to dungeons whose floors are stacked on top of one another - they occupy near identical ingame X/Y and the
 * builder has no Z axis to tell them apart. Once a boss on floor N has died the party has left every floor before N
 * behind, so those floors are excluded for the rest of the run.
 *
 * Deliberately an allowlist: enabling this everywhere shifts the pull counts of seven other dungeons' regression
 * runs, so the rule is not universally safe. Introduced in #4140.
 */
class BossKillFloorCutoffRule extends AbstractDungeonRouteBuilderRule
{
    private const array DUNGEON_KEYS = [
        DungeonKey::VOIDSCAR_ARENA->value,
        DungeonKey::THE_AZURE_VAULT->value,
    ];

    /**
     * @var int The lowest Floor index that is still eligible for matching. 0 means no cutoff yet.
     */
    private int $minimumFloorIndex = 0;

    public function __construct(private readonly DungeonRouteBuilderLoggingInterface $log)
    {
    }

    public function appliesToDungeon(Dungeon $dungeon): bool
    {
        return in_array($dungeon->key, self::DUNGEON_KEYS);
    }

    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): void
    {
        // The boss may not have been resolved to a mapped enemy at all - then we have no floor to work with
        if ($resolvedEnemy === null || !$resolvedEnemy->npc->isBoss() || $resolvedEnemy->floor->facade) {
            return;
        }

        if ($resolvedEnemy->floor->index <= $this->minimumFloorIndex) {
            return;
        }

        $this->minimumFloorIndex = $resolvedEnemy->floor->index;

        $this->log->applyBossKillFloorCutoffMinimumFloorIndexRaised(
            $resolvedEnemy->id,
            $resolvedEnemy->npc_id,
            $resolvedEnemy->floor_id,
            $this->minimumFloorIndex,
        );
    }

    /**
     * Soft rather than hard: an enemy that is only mapped on a floor before the last killed boss' floor would
     * otherwise be dropped from the route - and from its enemy forces - entirely. Rather than lose it, the builder
     * falls back to matching without the cutoff, where the worst case is the behaviour we had before it existed.
     */
    public function isEnemyEligibleOnFirstPass(Enemy $enemy): bool
    {
        return $this->minimumFloorIndex === 0 || $enemy->floor->index >= $this->minimumFloorIndex;
    }

    public function hasActiveFirstPassExclusion(): bool
    {
        return $this->minimumFloorIndex > 0;
    }

    public function getMinimumFloorIndex(): int
    {
        return $this->minimumFloorIndex;
    }
}
