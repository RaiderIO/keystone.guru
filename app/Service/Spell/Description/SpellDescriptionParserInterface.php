<?php

namespace App\Service\Spell\Description;

/**
 * Renders the game client's spell description templates into plain, readable text.
 */
interface SpellDescriptionParserInterface
{
    /**
     * Render `$spellId`'s description template. Tokens that cannot be resolved from the given context -
     * a value that only exists on a real character, a table we do not read - are omitted rather than
     * left in the output, so a description never shows its raw template.
     */
    public function parse(SpellDescriptionContextInterface $context, int $spellId, string $template): string;
}
