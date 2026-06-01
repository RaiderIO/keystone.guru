<?php

namespace App\Logic\CombatLog\SpecialEvents\Interfaces;

interface HasCombatLogDungeonContextInterface
{
    public function getChallengeModeID(): int;

    /**
     * Returns the key level, or null if not embedded in this event (falls back to run context).
     */
    public function getKeyLevel(): ?int;

    /**
     * Returns the affix IDs, or null if not embedded in this event (falls back to run context).
     *
     * @return int[]|null
     */
    public function getAffixIDs(): ?array;
}
