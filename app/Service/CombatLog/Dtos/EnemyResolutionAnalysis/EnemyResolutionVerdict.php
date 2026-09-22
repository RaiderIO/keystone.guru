<?php

namespace App\Service\CombatLog\Dtos\EnemyResolutionAnalysis;

/**
 * What a group of long Auto Route Creator enemy resolutions most likely means - the answer to "the builder keeps
 * matching this pack from far away, what do I do about it".
 *
 * An engagement's position is the npc's own position at the first log line it appears in, so it is not always where
 * the npc stood before it was pulled.
 */
enum EnemyResolutionVerdict: string
{
    /** Engaged consistently off in one direction, keeping its shape - the pack (or enemy) is mapped in the wrong place. */
    case Displaced = 'displaced';

    /**
     * Engaged consistently off in one direction, but bunched up - it ran to the group before anything was logged. The
     * engagement spot is where the group fights, not where the pack stands, so moving it there would be wrong.
     */
    case Converged = 'converged';

    /** Engaged off in all directions - body pulls, patrols or plain noise, nothing to move. */
    case Scatter = 'scatter';

    /**
     * Lower is more actionable - the order the rundown lists verdicts in.
     */
    public function severity(): int
    {
        return match ($this) {
            self::Displaced => 0,
            self::Converged => 1,
            self::Scatter   => 2,
        };
    }

    public function label(): string
    {
        return __(sprintf('services.combatlog.enemy_resolution_analysis.verdict.%s', $this->value));
    }

    /**
     * The colour the group is drawn in on the admin distance heatmap page.
     */
    public function color(): string
    {
        return match ($this) {
            self::Displaced => '#e74c3c',
            self::Converged => '#3498db',
            self::Scatter   => '#95a5a6',
        };
    }
}
