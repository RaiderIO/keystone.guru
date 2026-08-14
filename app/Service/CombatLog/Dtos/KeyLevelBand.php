<?php

namespace App\Service\CombatLog\Dtos;

/**
 * A range of keystone levels that is polled and budgeted as one unit.
 */
readonly class KeyLevelBand
{
    /**
     * @param int  $min The lowest keystone level in this band (inclusive).
     * @param ?int $max The highest keystone level in this band (inclusive). Null means the band is
     *                  open ended - only the top band is, and it is never budgeted.
     */
    public function __construct(
        public int  $min,
        public ?int $max,
    ) {
    }

    public function isTopBand(): bool
    {
        return $this->max === null;
    }

    public function __toString(): string
    {
        return $this->max === null
            ? sprintf('%d+', $this->min)
            : sprintf('%d-%d', $this->min, $this->max);
    }
}
