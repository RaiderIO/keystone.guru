<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * A rendered description, kept as a format string and the numbers that go in it rather than as one
 * finished sentence - so a consumer can put different numbers in later (see #3951).
 */
class RenderedSpellDescription
{
    /**
     * @param string                            $format sprintf format with positional placeholders (`%1$s`)
     * @param array<int, SpellDescriptionValue> $values one per placeholder, in order
     */
    public function __construct(
        public readonly string $format,
        public readonly array  $values,
    ) {
    }

    /**
     * The description as it reads with the values it was rendered with.
     */
    public function render(): string
    {
        if ($this->values === []) {
            return $this->format;
        }

        return vsprintf($this->format, array_map(
            static fn(SpellDescriptionValue $value): string => $value->text,
            $this->values,
        ));
    }

    public function isEmpty(): bool
    {
        return trim($this->format) === '';
    }
}
