<?php

namespace App\Service\CombatLog\DataExtractors\ImmunityBypasses;

use App\Models\CombatLog\SpellProperty;

/**
 * The single, data-only implementation of an immunity definition - every immunity differs in its values, not in its
 * behaviour. The instances live in {@see ImmunityDefinitions}.
 */
final class ImmunityDefinition implements ImmunityDefinitionInterface
{
    /**
     * @param array<int> $buffSpellIds
     */
    public function __construct(
        private readonly SpellProperty $property,
        private readonly int           $immunityBit,
        private readonly array         $buffSpellIds,
        private readonly int           $protectedSchoolsMask,
        private readonly bool          $blocksDamage,
        private readonly bool          $blocksHarmfulAuras,
        private readonly int           $maxDurationMs,
    ) {
    }

    public function getProperty(): SpellProperty
    {
        return $this->property;
    }

    public function getImmunityBit(): int
    {
        return $this->immunityBit;
    }

    /**
     * @return array<int>
     */
    public function getBuffSpellIds(): array
    {
        return $this->buffSpellIds;
    }

    public function getProtectedSchoolsMask(): int
    {
        return $this->protectedSchoolsMask;
    }

    public function blocksDamage(): bool
    {
        return $this->blocksDamage;
    }

    public function blocksHarmfulAuras(): bool
    {
        return $this->blocksHarmfulAuras;
    }

    public function getMaxDurationMs(): int
    {
        return $this->maxDurationMs;
    }
}
