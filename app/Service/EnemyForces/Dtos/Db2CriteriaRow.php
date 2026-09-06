<?php

namespace App\Service\EnemyForces\Dtos;

/** A row underneath a dungeon's enemy forces node that does not award forces for killing a creature. */
class Db2CriteriaRow
{
    public function __construct(
        public readonly int $criteriaId,
        public readonly int $type,
        public readonly int $asset,
        public readonly int $amount,
    ) {
    }
}
