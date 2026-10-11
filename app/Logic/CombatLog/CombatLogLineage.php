<?php

namespace App\Logic\CombatLog;

/**
 * A family of WoW clients whose combat log formats evolve along one line. Versions of different
 * lineages are never compared to each other, so a new Classic build never falls into a Retail format.
 */
enum CombatLogLineage: string
{
    case Classic = 'classic';
    case Retail  = 'retail';

    /**
     * Classic clients write combat log version 9, Retail clients 20 and up.
     */
    private const int FIRST_RETAIL_COMBAT_LOG_VERSION_NUMBER = 10;

    public static function fromCombatLogVersion(int $combatLogVersion): self
    {
        return intdiv($combatLogVersion, 1_000_000_000) < self::FIRST_RETAIL_COMBAT_LOG_VERSION_NUMBER
            ? self::Classic
            : self::Retail;
    }

    public function getOldestSupportedCombatLogVersion(): int
    {
        return match ($this) {
            self::Classic => CombatLogVersion::CLASSIC,
            self::Retail  => CombatLogVersion::RETAIL_10_1_0,
        };
    }
}
