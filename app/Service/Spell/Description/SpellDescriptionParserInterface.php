<?php

namespace App\Service\Spell\Description;

use App\Service\Spell\Description\Dtos\RenderedSpellDescription;

/**
 * Renders the game client's spell description templates into readable text.
 */
interface SpellDescriptionParserInterface
{
    /**
     * Render `$spellId`'s description template into a format string plus the numbers that go in it.
     *
     * Tokens that cannot be resolved from the given context - a value that only exists on a real
     * character, a table we do not read - are omitted rather than left in the output, so a description
     * never shows its raw template.
     *
     * @param float $damageMultiplier what a damage or healing coefficient is multiplied by for the
     *                                content the caster belongs to; 0 leaves coefficients as they are
     */
    public function parse(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $template,
        float                            $damageMultiplier = 0.0,
    ): RenderedSpellDescription;
}
