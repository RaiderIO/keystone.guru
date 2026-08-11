<?php

namespace App\Service\Spell\Description;

use App\Service\Spell\Description\Dtos\RenderedSpellDescription;
use App\Service\Spell\Description\Dtos\SpellDescriptionValue;

/**
 * Collects a description as it is parsed: literal text goes into a format string, and every number
 * becomes a placeholder with its value recorded alongside.
 *
 * Descriptions are full of literal percent signs ("slowing movement speed by 50%"), so all literal text
 * is escaped on the way in - without that, the stored format would be unsafe to hand to sprintf.
 */
class SpellDescriptionBuilder
{
    private string $format = '';

    /** @var array<int, SpellDescriptionValue> */
    private array $values = [];

    public function appendText(string $text): void
    {
        $this->format .= str_replace('%', '%%', $text);
    }

    public function appendValue(SpellDescriptionValue $value): void
    {
        $this->values[] = $value;
        $this->format .= sprintf('%%%d$s', count($this->values));
    }

    public function build(): RenderedSpellDescription
    {
        return new RenderedSpellDescription(trim($this->format), $this->values);
    }
}
