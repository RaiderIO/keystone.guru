<?php

namespace App\Service\Spell\Description;

use App\Service\Spell\Description\Dtos\SpellEffectData;

/**
 * Everything the description parser may need to look up while rendering a template. Descriptions freely
 * reference other spells (`$319949s1`), so a context always spans every spell of a game build rather
 * than the one being rendered.
 */
interface SpellDescriptionContextInterface
{
    /** @param int $effectIndex 0-based, i.e. `$s1` asks for effect index 0 */
    public function getEffect(int $spellId, int $effectIndex): ?SpellEffectData;

    /** The spell's duration in milliseconds, -1 when it lasts until cancelled. */
    public function getDurationMs(int $spellId): ?int;

    public function getName(int $spellId): ?string;

    public function getDescriptionTemplate(int $spellId): ?string;

    /**
     * The spell's named description variables (`$<mult>`), keyed by name, with their raw expressions as
     * values.
     *
     * @return array<string, string>
     */
    public function getDescriptionVariables(int $spellId): array;
}
