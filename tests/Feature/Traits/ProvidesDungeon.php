<?php

namespace Tests\Feature\Traits;

use App\Models\Dungeon;
use Closure;
use Illuminate\Database\Eloquent\Builder;

trait ProvidesDungeon
{
    /**
     * Returns a random dungeon guaranteed to have a current mapping version and at least
     * one non-facade floor. Use this instead of `Dungeon::inRandomOrder()->first()` to
     * avoid intermittent test failures when the random pick lacks these prerequisites.
     *
     * @param (Closure(Builder<Dungeon>): mixed)|null $constraint Optional extra constraint applied to the base query.
     */
    protected function getDungeonWithNonFacadeFloor(?Closure $constraint = null): Dungeon
    {
        $count = 0;
        do {
            if (++$count > 20) {
                throw new \RuntimeException('Unable to find a dungeon with non-facade floors only and a mapping version');
            }

            $query = Dungeon::query();
            if ($constraint !== null) {
                $constraint($query);
            }

            /** @var Dungeon $dungeon */
            $dungeon = $query->inRandomOrder()->first();
        } while (
            $dungeon->getCurrentMappingVersion() === null ||
            $dungeon->floors()->where('facade', 0)->doesntExist() ||
            $dungeon->floors()->where('facade', 1)->exists()
        );

        return $dungeon;
    }
}
