<?php

namespace App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\Enemy;

/**
 * A per-dungeon exception to the Auto Route Creator's normal enemy matching.
 *
 * Spatial matching alone cannot resolve every dungeon: floors stacked on top of one another, or a bridge and the
 * ground underneath it, put two sets of enemies with the same npc_ids in near identical ingame X/Y with no Z axis to
 * tell them apart. A rule adds the missing information - usually "how far into the run are we" - as builder state
 * that the matcher can then filter or bias on.
 *
 * Rules are instantiated per build() and carry mutable state, so they must never be registered as singletons.
 */
interface DungeonRouteBuilderRuleInterface
{
    /**
     * Whether this rule should be active for the route being built. Called once, before the first enemy is matched.
     */
    public function appliesToDungeon(Dungeon $dungeon): bool;

    /**
     * Advance the rule's state because an enemy died, and award the kills that this death implies.
     *
     * Keyed on the npc_id as it was logged rather than on $resolvedEnemy, because a boss that failed to resolve to a
     * mapped enemy would otherwise never advance a rule that depends on it.
     *
     * Some enemies despawn instead of dying, so their death never reaches us at all - not from the combat log, and not
     * from what Raider.IO sends the Auto Route Creator. A rule compensates by naming the npc_ids whose kill should be
     * awarded in the same pull as this death; the builder then resolves and attaches them as if they had been sent.
     * Awarding is the rule's own responsibility to keep idempotent - the builder will happily award the same npc twice.
     *
     * @return array<int, int> npc_ids to award a kill for, empty when this death awards nothing
     */
    public function onEnemyDied(int $npcId, ?Enemy $resolvedEnemy): array;

    /**
     * Whether this enemy may be matched at all. A false here is final - it survives the builder's retry for an NPC
     * that matched nothing, so the enemy is dropped from the route (and its enemy forces) rather than mismatched.
     */
    public function isEnemyEligible(Enemy $enemy): bool;

    /**
     * Whether this enemy may be matched on the first pass. Unlike isEnemyEligible(), a false here is reconsidered
     * when the NPC matched nothing at all, so the enemy is only excluded while a better candidate exists.
     */
    public function isEnemyEligibleOnFirstPass(Enemy $enemy): bool;

    /**
     * Whether isEnemyEligibleOnFirstPass() is currently excluding anything. When no rule is, the builder can skip its
     * retry entirely - there is nothing for a second pass to find.
     */
    public function hasActiveFirstPassExclusion(): bool;
}
