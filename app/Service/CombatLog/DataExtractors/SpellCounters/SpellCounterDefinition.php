<?php

namespace App\Service\CombatLog\DataExtractors\SpellCounters;

/**
 * Defaults shared by every counter definition: the archetype is a threat drop that is triggered by its own cast and
 * puts no requirement on the debuff it makes go away.
 */
abstract class SpellCounterDefinition implements SpellCounterDefinitionInterface
{
    /**
     * @return array<int>
     */
    public function getTriggerAuraSpellIds(): array
    {
        return [];
    }

    public function getCharacterClassKey(): ?string
    {
        return null;
    }

    public function getCharacterRaceKey(): ?string
    {
        return null;
    }

    public function dropsThreat(): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function getUnstrippableDebuffDispelTypes(): array
    {
        return [];
    }
}
