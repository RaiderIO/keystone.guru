<?php

namespace App\Service\CombatLog\DataExtractors\SpellCounters;

use Illuminate\Support\Collection;

final class SpellCounterDefinitions
{
    /** @var Collection<int, SpellCounterDefinitionInterface>|null */
    private static ?Collection $definitions = null;

    /**
     * @return Collection<int, SpellCounterDefinitionInterface>
     */
    public static function all(): Collection
    {
        return self::$definitions ??= collect([
            new VanishSpellCounterDefinition(),
            new ShadowmeldSpellCounterDefinition(),
            new FeignDeathSpellCounterDefinition(),
            new InvisibilitySpellCounterDefinition(),
            new CloakOfShadowsSpellCounterDefinition(),
        ]);
    }
}
