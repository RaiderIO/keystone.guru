<?php

namespace App\Service\Spell\Description;

use App\Service\Spell\Description\Dtos\SpellEffectData;

/**
 * An in-memory context, built up by the importer while it streams the DB2 CSVs.
 */
class ArraySpellDescriptionContext implements SpellDescriptionContextInterface
{
    /**
     * @param array<int, array<int, SpellEffectData>> $effects              spell id => effect index => effect
     * @param array<int, int>                         $durationsMs          spell id => duration in milliseconds
     * @param array<int, string>                      $names                spell id => name
     * @param array<int, string>                      $templates            spell id => raw description template
     * @param array<int, array<string, string>>       $descriptionVariables spell id => variable name => expression
     */
    public function __construct(
        private readonly array $effects = [],
        private readonly array $durationsMs = [],
        private readonly array $names = [],
        private readonly array $templates = [],
        private readonly array $descriptionVariables = [],
    ) {
    }

    public function getEffect(int $spellId, int $effectIndex): ?SpellEffectData
    {
        return $this->effects[$spellId][$effectIndex] ?? null;
    }

    public function getDurationMs(int $spellId): ?int
    {
        return $this->durationsMs[$spellId] ?? null;
    }

    public function getName(int $spellId): ?string
    {
        return $this->names[$spellId] ?? null;
    }

    public function getDescriptionTemplate(int $spellId): ?string
    {
        return $this->templates[$spellId] ?? null;
    }

    public function getDescriptionVariables(int $spellId): array
    {
        return $this->descriptionVariables[$spellId] ?? [];
    }
}
