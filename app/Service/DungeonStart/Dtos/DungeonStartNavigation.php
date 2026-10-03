<?php

namespace App\Service\DungeonStart\Dtos;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;

/**
 * Where clicking a dungeon start on an explore page leads.
 */
class DungeonStartNavigation
{
    /**
     * @param bool        $isBackLink  True when the start has no target and leads back to the start pointing at its dungeon.
     * @param GameVersion $gameVersion The game version to open the dungeon in.
     * @param Dungeon     $dungeon     The dungeon to open.
     * @param Floor|null  $floor       The floor to land on, or null for the dungeon's default floor.
     */
    public function __construct(
        public readonly bool        $isBackLink,
        public readonly GameVersion $gameVersion,
        public readonly Dungeon     $dungeon,
        public readonly ?Floor      $floor,
    ) {
    }

    public function getUrl(): string
    {
        return $this->floor === null ?
            route('dungeon.explore.gameversion.view', [
                'gameVersion' => $this->gameVersion,
                'dungeon'     => $this->dungeon,
            ]) :
            route('dungeon.explore.gameversion.view.floor', [
                'gameVersion' => $this->gameVersion,
                'dungeon'     => $this->dungeon,
                'floorIndex'  => $this->floor->index,
            ]);
    }
}
