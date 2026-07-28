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
     * disabled and EXACTLY one non-facade floor. Use this instead of getDungeonWithNonFacadeFloor()
     * when a test seeds a single thumbnail row and asserts on freshness/count, since several seeded
     * dungeons (e.g. Karazhan) have many non-facade floors and would make such an assertion flaky.
     *
     * @param (Closure(Builder<Dungeon>): mixed)|null $constraint Optional extra constraint applied to the base query.
     */
    protected function getDungeonWithExactlyOneNonFacadeFloor(?Closure $constraint = null): Dungeon
    {
        $count = 0;
        do {
            if (++$count > 20) {
                throw new \RuntimeException('Unable to find a dungeon with exactly one non-facade floor and a mapping version');
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
            // Mirrors floorsForMapFacade(...)->active(): an inactive floor row is never dispatched a job
            // and must not be counted as "expected", or the freshness check's floor count would disagree
            // with reality.
            $dungeon->floors()->where('facade', 0)->where('active', 1)->count() !== 1 ||
            $dungeon->floors()->where('facade', 1)->exists()
        );

        return $dungeon;
    }

    /**
     * Returns a random dungeon guaranteed to have a current mapping version with facade rendering
     * disabled and at least two active non-facade floors. Use this to exercise "missing thumbnail for
     * one of several floors" scenarios.
     *
     * @param (Closure(Builder<Dungeon>): mixed)|null $constraint Optional extra constraint applied to the base query.
     */
    protected function getDungeonWithMultipleNonFacadeFloors(?Closure $constraint = null): Dungeon
    {
        $count = 0;
        do {
            if (++$count > 20) {
                throw new \RuntimeException('Unable to find a dungeon with multiple non-facade floors and a mapping version');
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
            // Mirrors floorsForMapFacade(...)->active(): see getDungeonWithExactlyOneNonFacadeFloor().
            $dungeon->floors()->where('facade', 0)->where('active', 1)->count() < 2 ||
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
