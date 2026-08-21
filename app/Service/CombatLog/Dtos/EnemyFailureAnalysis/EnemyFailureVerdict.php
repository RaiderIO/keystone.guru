<?php

namespace App\Service\CombatLog\Dtos\EnemyFailureAnalysis;

/**
 * What a cluster of Auto Route Creator enemy failures most likely means for the mapping - the answer to "why did the
 * builder fail to place this npc here, over and over".
 */
enum EnemyFailureVerdict: string
{
    /** The mapping version has no enemy with this npc id at all - the npc (or a whole pack of it) is not mapped. */
    case NpcNotMapped = 'npc_not_mapped';

    /** Enemies of this npc exist, but none within engagement range of the cluster - a pack is missing or misplaced here. */
    case NoEnemyInRange = 'no_enemy_in_range';

    /** Enemies of this npc sit within range, yet routes still fail here - the game has more of them than the mapping. */
    case EnemiesExhausted = 'enemies_exhausted';

    /**
     * No same-floor enemy in range, but one IS in range on another floor. An unresolved npc's floor is inferred from
     * the previous npc in the log, so this is most likely that inference being wrong rather than a mapping problem.
     */
    case WrongFloorArtifact = 'wrong_floor_artifact';

    /**
     * Lower is more urgent - the order the rundown lists verdicts in.
     */
    public function severity(): int
    {
        return match ($this) {
            self::NpcNotMapped       => 0,
            self::NoEnemyInRange     => 1,
            self::EnemiesExhausted   => 2,
            self::WrongFloorArtifact => 3,
        };
    }

    public function label(): string
    {
        return __(sprintf('services.combatlog.enemy_failure_analysis.verdict.%s', $this->value));
    }

    /**
     * The colour the cluster is drawn in on the admin heatmap page.
     */
    public function color(): string
    {
        return match ($this) {
            self::NpcNotMapped       => '#e74c3c',
            self::NoEnemyInRange     => '#f39c12',
            self::EnemiesExhausted   => '#3498db',
            self::WrongFloorArtifact => '#95a5a6',
        };
    }
}
