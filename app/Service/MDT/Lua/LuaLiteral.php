<?php

namespace App\Service\MDT\Lua;

use Stringable;

/**
 * A raw piece of Lua source that must be emitted verbatim rather than re-serialized.
 *
 * MDT's dungeon files carry values we cannot reproduce byte-for-byte from our own data - full
 * precision coordinates, `L["..."]` translation keys and string concatenations. Wrapping them
 * keeps their exact source text intact all the way through to the exported file.
 */
class LuaLiteral implements Stringable
{
    public function __construct(private readonly string $literal)
    {
    }

    public function getLiteral(): string
    {
        return $this->literal;
    }

    public function toFloat(): float
    {
        return (float)$this->literal;
    }

    public function toInt(): int
    {
        return (int)$this->literal;
    }

    /**
     * @return string The literal with its surrounding quotes removed, if it was a quoted string.
     */
    public function toUnquotedString(): string
    {
        if (preg_match('/^"(.*)"$/s', $this->literal, $matches) === 1) {
            return stripslashes($matches[1]);
        }

        if (preg_match("/^'(.*)'\$/s", $this->literal, $matches) === 1) {
            return stripslashes($matches[1]);
        }

        return $this->literal;
    }

    public function __toString(): string
    {
        return $this->literal;
    }
}
