<?php

namespace Tests\Feature\Traits;

use App\Models\Dungeon;
use Closure;
use Illuminate\Database\Eloquent\Builder;

trait ProvidesDungeon
{
    /**
     * Returns a random dungeon guaranteed to have a current mapping version with facade rendering
     * disabled and at least one non-facade floor. Use this instead of `Dungeon::inRandomOrder()->first()`
     * to avoid intermittent test failures when the random pick lacks these prerequisites.
     *
     * The facade_enabled guard matters because `Dungeon::floorsForMapFacade($mappingVersion, true)`
     * returns only facade floors when the mapping version is facade_enabled - a dungeon in that state
     * with no facade floor row yields an empty floor set, which silently breaks callers that iterate it.
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
            ($mappingVersion = $dungeon->getCurrentMappingVersion()) === null ||
            $mappingVersion->facade_enabled ||
            $dungeon->floors()->where('facade', 0)->doesntExist() ||
            $dungeon->floors()->where('facade', 1)->exists()
        );

        return $dungeon;
    }

    /**
     * Returns a random dungeon guaranteed to have a current mapping version with facade rendering
     * enabled and at least one facade floor. Use this when a test needs to exercise the
     * facade-specific code paths.
     *
     * The facade_enabled guard mirrors getDungeonWithNonFacadeFloor(): a facade floor row without a
     * facade_enabled mapping version would make `Dungeon::floorsForMapFacade($mappingVersion, true)`
     * return the non-facade floors instead, defeating the point of a facade fixture.
     *
     * @param (Closure(Builder<Dungeon>): mixed)|null $constraint Optional extra constraint applied to the base query.
     */
    protected function getDungeonWithFacadeFloor(?Closure $constraint = null): Dungeon
    {
        $count = 0;
        do {
            if (++$count > 20) {
                throw new \RuntimeException('Unable to find a dungeon with a facade floor and a mapping version');
            }

            $query = Dungeon::query();
            if ($constraint !== null) {
                $constraint($query);
            }

            /** @var Dungeon $dungeon */
            $dungeon = $query->inRandomOrder()->first();
        } while (
            ($mappingVersion = $dungeon->getCurrentMappingVersion()) === null ||
            !$mappingVersion->facade_enabled ||
            $dungeon->floors()->where('facade', 1)->doesntExist()
        );

        return $dungeon;
    }
}
