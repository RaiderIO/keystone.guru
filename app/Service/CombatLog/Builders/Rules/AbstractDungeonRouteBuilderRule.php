<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Enemy;

/**
 * Supplies the do-nothing behaviour for every hook, so a concrete rule only implements the ones it actually uses, and
 * the bookkeeping shared by every "award a kill for an npc whose death never reaches us" rule.
 */
abstract class AbstractDungeonRouteBuilderRule implements DungeonRouteBuilderRuleInterface
{
    /** @var array<int, bool> npc_id => true for every enemy this rule has accounted for, whether it died normally or was awarded */
    private array $defeatedNpcIds = [];

    /**
     * Marks every died npc_id as accounted for, so a subclass using the bookkeeping below doesn't have to remember
     * to call markDefeated() itself - it only has to call parent::onEnemyDied() once, at the top of its override.
     */
    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): array
    {
        $this->markDefeated($npcId);

        return [];
    }

    public function onRunFinished(?bool $success): array
    {
        return [];
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

    /**
     * Marks $npcId as accounted for, whether it died normally or was awarded. Called automatically from
     * onEnemyDied() above for every npc_id a subclass receives, so a later isDefeated()/awardUnaccountedNpcIds()
     * call sees it.
     */
    protected function markDefeated(int $npcId): void
    {
        $this->defeatedNpcIds[$npcId] = true;
    }

    protected function isDefeated(int $npcId): bool
    {
        return isset($this->defeatedNpcIds[$npcId]);
    }

    /**
     * Filters $npcIds down to the ones not yet accounted for, and marks each of those as accounted for too. Logging
     * the result is the subclass's own responsibility - the log line's shape is rule-specific.
     *
     * @param array<int, int> $npcIds
     *
     * @return array<int, int>
     */
    protected function awardUnaccountedNpcIds(array $npcIds): array
    {
        $awardedNpcIds = [];

        foreach ($npcIds as $npcId) {
            if ($this->isDefeated($npcId)) {
                continue;
            }

            $this->markDefeated($npcId);
            $awardedNpcIds[] = $npcId;
        }

        return $awardedNpcIds;
    }
}
