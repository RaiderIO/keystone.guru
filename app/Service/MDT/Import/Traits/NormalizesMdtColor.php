<?php

namespace App\Service\MDT\Import\Traits;

trait NormalizesMdtColor
{
    /**
     * Turns an MDT colour (with or without a leading pound sign) into a `#rrggbb`/`#rgb` hex colour, or
     * returns the fallback when the value is not a hex colour at all.
     */
    private function normalizeMdtColor(mixed $color, ?string $fallback): ?string
    {
        if (!is_string($color) && !is_int($color)) {
            return $fallback;
        }

        $color = (string)$color;
        if (!str_starts_with($color, '#')) {
            $color = sprintf('#%s', $color);
        }

        return preg_match('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $color) === 1 ? $color : $fallback;
    }
}
