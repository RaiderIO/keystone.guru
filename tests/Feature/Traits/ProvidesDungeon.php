<?php

namespace Tests\Feature\Traits;

use App\Models\Dungeon;

trait ProvidesDungeon
{
    /**
     * Returns a random dungeon guaranteed to have a current mapping version and at least
     * one non-facade floor. Use this instead of `Dungeon::inRandomOrder()->first()` to
     * avoid intermittent test failures when the random pick lacks these prerequisites.
     */
    protected function getDungeonWithNonFacadeFloor(): Dungeon
    {
        $count = 0;
        do {
            if (++$count > 20) {
                throw new \RuntimeException('Unable to find a dungeon with non-facade floors only and a mapping version');
            }
            /** @var Dungeon $dungeon */
            $dungeon = Dungeon::inRandomOrder()->first();
        } while (
            $dungeon->getCurrentMappingVersion() === null ||
            $dungeon->floors()->where('facade', 0)->doesntExist() ||
            $dungeon->floors()->where('facade', 1)->exists()
        );

        return $dungeon;
    }
}
