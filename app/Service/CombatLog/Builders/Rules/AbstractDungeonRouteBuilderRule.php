<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Enemy;

/**
 * Supplies the do-nothing behaviour for every hook, so a concrete rule only implements the ones it actually uses.
 */
abstract class AbstractDungeonRouteBuilderRule implements DungeonRouteBuilderRuleInterface
{
    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): void
    {
        // Stateless by default
    }

    public function isEnemyEligible(Enemy $enemy): bool
    {
        return true;
    }

    public function isEnemyEligibleOnFirstPass(Enemy $enemy): bool
    {
        return true;
    }

    public function hasActiveFirstPassExclusion(): bool
    {
        return false;
    }
}
