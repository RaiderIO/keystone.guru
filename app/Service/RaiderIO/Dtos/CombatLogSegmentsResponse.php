<?php

namespace App\Service\RaiderIO\Dtos;

readonly class CombatLogSegmentsResponse
{
    /**
     * @param CombatLogSegment[] $segments
     */
    public function __construct(
        public int   $sourceUserId,
        public array $segments,
    ) {
    }
}
