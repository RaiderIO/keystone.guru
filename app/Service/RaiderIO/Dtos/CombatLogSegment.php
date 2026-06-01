<?php

namespace App\Service\RaiderIO\Dtos;

readonly class CombatLogSegment
{
    public function __construct(
        public int    $id,
        public string $type,
        public string $downloadUrl,
    ) {
    }
}
