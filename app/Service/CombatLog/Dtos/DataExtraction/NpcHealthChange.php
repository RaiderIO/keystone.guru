<?php

namespace App\Service\CombatLog\Dtos\DataExtraction;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;

/**
 * What one NPC's observed max HP means for its npc_healths row: the base health it reverses to, next to what the
 * row holds today. Produced by NpcHealthExtractionService::compareNpcHealths(), consumed by applyNpcHealths().
 */
readonly class NpcHealthChange
{
    public function __construct(
        public Npc                  $npc,
        public ?NpcHealth           $existingNpcHealth,
        public NpcHealthObservation $observation,
        public float                $scalingFactor,
        /** The observed max HP with the key-level scaling reversed - the real base health */
        public int                  $observedBaseHealth,
        /** What the `health` column must hold so calculateHealthForKey() reproduces the observed max HP - differs from $observedBaseHealth only when the row carries a percentage */
        public int                  $newHealth,
        /** Whether this NPC's health is hand-curated (Npc::getCuratedDataNpcIds()) and must be left alone whatever the log says */
        public bool                 $curated = false,
    ) {
    }

    public function isMissing(): bool
    {
        return $this->existingNpcHealth === null;
    }

    public function isPlaceholder(): bool
    {
        return $this->existingNpcHealth !== null && $this->existingNpcHealth->health === NpcHealth::HEALTH_PLACEHOLDER;
    }

    /**
     * Within one part per million (and never less than 1 HP) counts as unchanged: the reversal divides and rounds, so a
     * row that was itself derived the same way (every MDT base was) lands a few HP off on a 50M boss without anything
     * having changed.
     */
    public function isUnchanged(): bool
    {
        if ($this->existingNpcHealth === null) {
            return false;
        }

        return abs($this->existingNpcHealth->health - $this->newHealth) <= max(1, $this->existingNpcHealth->health / 1_000_000);
    }

    /**
     * Relative difference between the stored health and the observed one - null when there is nothing real to
     * compare against (no row, or the placeholder).
     */
    public function getDeltaPercent(): ?float
    {
        if ($this->existingNpcHealth === null || $this->isPlaceholder() || $this->existingNpcHealth->health === 0) {
            return null;
        }

        return (($this->newHealth - $this->existingNpcHealth->health) / $this->existingNpcHealth->health) * 100;
    }

    /**
     * Missing and placeholder rows are always written; a real value only when the caller asked to overwrite. A curated
     * NPC is never written at all - its stored health deliberately differs from what the game shows, so an observation
     * that disagrees with it is the expected outcome rather than a correction.
     */
    public function shouldWrite(bool $overwrite): bool
    {
        if ($this->curated) {
            return false;
        }

        if ($this->isMissing() || $this->isPlaceholder()) {
            return true;
        }

        return $overwrite && !$this->isUnchanged();
    }
}
