<?php

namespace App\Models\Traits;

/**
 * For an int-backed enum whose cases are single bits of a mask column, and whose slugs double as translation-key
 * suffixes.
 */
trait BitmaskEnum
{
    abstract public function slug(): string;

    /**
     * Every case's slug keyed by its bit, in declaration order - the shape
     * {@see \App\Models\Spell\Spell::maskToReadableString()} and the map context's static data expect.
     *
     * @return array<int, string>
     */
    public static function slugsByBit(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[$case->value] = $case->slug();
        }

        return $result;
    }

    /**
     * Whether this case's bit is set in the given mask.
     */
    public function isSetIn(int $mask): bool
    {
        return ($mask & $this->value) !== 0;
    }
}
