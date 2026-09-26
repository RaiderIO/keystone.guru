<?php

namespace App\Service\DungeonRoute\Dtos;

/**
 * The pull a diff entry belongs to, identified the way the author sees it on the map.
 */
readonly class MappingVersionUpgradeDiffPull
{
    public function __construct(
        /** The pull's index as shown in the sidebar, 1 based. */
        public int     $index,
        /** The pull's colour, so the row can carry the same swatch the map does. */
        public ?string $color,
    ) {
    }
}
