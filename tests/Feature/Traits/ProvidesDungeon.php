<?php

namespace Tests\Feature\Traits;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Selects a seeded dungeon that is guaranteed to satisfy everything the calling test needs.
 *
 * Use `findDungeon()` (or one of the presets below) instead of `Dungeon::inRandomOrder()->first()`.
 * Sampling a dungeon and only afterwards checking whether it is usable is what produced the
 * recurring CI flakes (#3679, #3710): the draw succeeded, and the test then failed on something the
 * draw never guaranteed - most often an enemy that the dungeon simply does not have.
 */
trait ProvidesDungeon
{
    /**
     * Finds a seeded dungeon satisfying every requirement passed, together with the mapping version
     * that was validated against.
     *
     * Pass requirements as *named* arguments - they are all optional and independent:
     * `$this->findDungeon(facadeEnabled: false, challengeMode: true, minEnemies: 1)`.
     *
     * This scans every candidate rather than sampling a few, so it cannot fail to find a dungeon
     * that exists. If a new requirement is needed, add a parameter here rather than filtering the
     * result afterwards in the test - a post-hoc filter is exactly the shape that keeps going flaky.
     *
     * The returned `MappingVersion` is the one every requirement was checked against, so tests
     * should use it verbatim. `Dungeon::getCurrentMappingVersion()` resolves against `Auth::user()`
     * and memoises per model instance, so re-resolving it on a freshly loaded `Dungeon` (as
     * `DungeonRouteSaveService::persist()` does) can yield a *different* row.
     *
     * @param  bool|null                                      $facadeEnabled       Require the current mapping version to (not) render a facade. `false` additionally excludes dungeons that have any facade floor at all.
     * @param  int                                            $minActiveFloors     Minimum number of active floors the dungeon renders, counted exactly as production does via `floorsForMapFacade()->active()`.
     * @param  int|null                                       $maxActiveFloors     Maximum number of those floors; `null` for no upper bound.
     * @param  bool|null                                      $challengeMode       Require the dungeon to (not) be a Mythic+ dungeon.
     * @param  bool|null                                      $speedrunEnabled     Require `speedrun_enabled` to equal this.
     * @param  bool|null                                      $dungeonActive       Require `dungeons.active` to equal this. Off by default: 20 of the 102 non-facade dungeons are inactive and tests have always been allowed to use them.
     * @param  bool                                           $requireDefaultFloor Require an active default floor, which the `dungeonroute.edit` redirect chain needs.
     * @param  int                                            $minEnemies          Minimum enemies on the current mapping version.
     * @param  int                                            $minEnemyPacks       Minimum enemy packs on the current mapping version.
     * @param  GameVersion|null                               $gameVersion         Require the current mapping version to belong to this game version, rather than the acting user's.
     * @param  bool                                           $shuffle             Set false to keep the query's own ordering, for fixtures that must stay deterministic.
     * @param  (Closure(Builder<Dungeon>): mixed)|null        $constraint          Escape hatch for extra SQL-level criteria.
     * @param  (Closure(Dungeon, MappingVersion): mixed)|null $resolve             Escape hatch for criteria that need the resolved mapping version. Return null to reject the candidate; anything else is accepted and returned as element 2. Derive from the mapping version rather than the dungeon instance: the closure is handed the scanned instance, while element 0 is re-loaded (see reloadDungeon()).
     * @return array{0: Dungeon, 1: MappingVersion, 2: mixed}
     */
    protected function findDungeon(
        ?bool        $facadeEnabled = null,
        int          $minActiveFloors = 1,
        ?int         $maxActiveFloors = null,
        ?bool        $challengeMode = null,
        ?bool        $speedrunEnabled = null,
        ?bool        $dungeonActive = null,
        bool         $requireDefaultFloor = false,
        int          $minEnemies = 0,
        int          $minEnemyPacks = 0,
        ?GameVersion $gameVersion = null,
        bool         $shuffle = true,
        ?Closure     $constraint = null,
        ?Closure     $resolve = null,
    ): array {
        $requirements = [];

        // The candidate query is never cached: `Dungeon` is a CacheModel, model caching is on in CI
        // but off locally, and a cached list would go stale for constraints that depend on rows
        // other tests create (e.g. whereDoesntHave('dungeonRoutes')) - writing a DungeonRoute does
        // not flush the Dungeon cache. The per-candidate requirement checks below query Enemy /
        // EnemyPack / Floor with caching left on, which is safe because those models' own writes
        // invalidate their tags.
        $query = Dungeon::query()->disableCache();

        // A dungeon without any mapping version can never resolve a current one
        $query->whereHas('mappingVersions');

        if ($challengeMode !== null) {
            $requirements[] = sprintf('challengeMode: %s', $challengeMode ? 'true' : 'false');

            if ($challengeMode) {
                $query->whereNotNull('challenge_mode_id');
            } else {
                $query->whereNull('challenge_mode_id');
            }
        }

        if ($speedrunEnabled !== null) {
            $requirements[] = sprintf('speedrunEnabled: %s', $speedrunEnabled ? 'true' : 'false');
            $query->where('speedrun_enabled', $speedrunEnabled);
        }

        if ($dungeonActive !== null) {
            $requirements[] = sprintf('dungeonActive: %s', $dungeonActive ? 'true' : 'false');
            $query->where('active', $dungeonActive);
        }

        if ($requireDefaultFloor) {
            $requirements[] = 'requireDefaultFloor: true';
            $query->whereHas('floors', static fn(Builder $query) => $query->where('default', 1)->where('active', 1));
        }

        if ($facadeEnabled !== null) {
            $requirements[] = sprintf('facadeEnabled: %s', $facadeEnabled ? 'true' : 'false');

            if ($facadeEnabled) {
                $query->whereHas('floors', static fn(Builder $query) => $query->where('facade', 1)->where('active', 1));
            } else {
                // A facade floor outliving the mapping version that enabled it still breaks callers
                // resolving floors by index, so exclude those dungeons outright rather than merely
                // requiring a non-facade floor to exist.
                $query->whereDoesntHave('floors', static fn(Builder $query) => $query->where('facade', 1));
            }
        }

        if ($gameVersion !== null) {
            $requirements[] = sprintf('gameVersion: %d', $gameVersion->id);
            $query->whereHas('mappingVersions', static fn(Builder $query) => $query->where('game_version_id', $gameVersion->id));
        }

        if ($constraint !== null) {
            $requirements[] = 'constraint';
            $constraint($query);
        }

        $candidates     = $query->get();
        $candidateCount = $candidates->count();

        if ($shuffle) {
            $candidates = $candidates->shuffle();
        }

        $requirements[] = sprintf('minActiveFloors: %d', $minActiveFloors);
        if ($maxActiveFloors !== null) {
            $requirements[] = sprintf('maxActiveFloors: %d', $maxActiveFloors);
        }
        if ($minEnemies > 0) {
            $requirements[] = sprintf('minEnemies: %d', $minEnemies);
        }
        if ($minEnemyPacks > 0) {
            $requirements[] = sprintf('minEnemyPacks: %d', $minEnemyPacks);
        }
        if ($resolve !== null) {
            $requirements[] = 'resolve';
        }

        /** @var array<string, int> $rejections */
        $rejections = [];

        foreach ($candidates as $dungeon) {
            /** @var Dungeon $dungeon */
            $mappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);

            if ($mappingVersion === null) {
                $rejections['no current mapping version'] = ($rejections['no current mapping version'] ?? 0) + 1;

                continue;
            }

            // getCurrentMappingVersion() falls back to the acting user's game version, then the
            // default, then simply the highest version - so asking for a game version has to be
            // verified, not merely requested, or a dungeon whose only mapping is for a different
            // game version satisfies the requirement.
            if ($gameVersion !== null && $mappingVersion->game_version_id !== $gameVersion->id) {
                $rejections['gameVersion'] = ($rejections['gameVersion'] ?? 0) + 1;

                continue;
            }

            if ($facadeEnabled !== null && (bool)$mappingVersion->facade_enabled !== $facadeEnabled) {
                $rejections['facadeEnabled'] = ($rejections['facadeEnabled'] ?? 0) + 1;

                continue;
            }

            // Counted exactly as production counts renderable floors, so a dungeon whose only
            // qualifying floor is inactive is never handed out
            $activeFloorCount = $dungeon->floorsForMapFacade($mappingVersion)->active()->count();

            if ($activeFloorCount < $minActiveFloors) {
                $rejections['minActiveFloors'] = ($rejections['minActiveFloors'] ?? 0) + 1;

                continue;
            }

            if ($maxActiveFloors !== null && $activeFloorCount > $maxActiveFloors) {
                $rejections['maxActiveFloors'] = ($rejections['maxActiveFloors'] ?? 0) + 1;

                continue;
            }

            if ($minEnemies > 0 && $mappingVersion->enemies()->count() < $minEnemies) {
                $rejections['minEnemies'] = ($rejections['minEnemies'] ?? 0) + 1;

                continue;
            }

            if ($minEnemyPacks > 0 && $mappingVersion->enemyPacks()->count() < $minEnemyPacks) {
                $rejections['minEnemyPacks'] = ($rejections['minEnemyPacks'] ?? 0) + 1;

                continue;
            }

            $match = true;
            if ($resolve !== null) {
                $match = $resolve($dungeon, $mappingVersion);

                // Strictly null, so a resolve() legitimately returning 0, '' or false is still a hit
                if ($match === null) {
                    $rejections['resolve'] = ($rejections['resolve'] ?? 0) + 1;

                    continue;
                }
            }

            // Re-fetched as a single model on purpose. `preventLazyLoading` only enforces against
            // models hydrated as part of a collection, so handing back a member of the scanned
            // collection would turn any caller that touches an un-eager-loaded relation (e.g.
            // `getEnabledSpeedrunDifficulties()`) into a LazyLoadingViolationException. The scan
            // needs a collection; callers expect the leniency of a single load.
            return [$this->reloadDungeon($dungeon), $mappingVersion, $match];
        }

        throw new RuntimeException($this->describeUnsatisfiableDungeon($requirements, $candidateCount, $rejections));
    }

    /**
     * Returns a random dungeon guaranteed to have a current mapping version with facade rendering
     * disabled and at least one active non-facade floor. Use this instead of `Dungeon::inRandomOrder()->first()`
     * to avoid intermittent test failures when the random pick lacks these prerequisites.
     *
     * Like the other presets this drops the validated `MappingVersion`, so callers re-resolve it
     * themselves. That is safe only while no acting user with a non-default game version is set -
     * see findDungeon()'s docblock. Call findDungeon() directly if a test acts as a user.
     *
     * The facade_enabled guard matters because `Dungeon::floorsForMapFacade($mappingVersion, true)`
     * returns only facade floors when the mapping version is facade_enabled - a dungeon in that state
     * with no facade floor row yields an empty floor set, which silently breaks callers that iterate it.
     *
     * @param (Closure(Builder<Dungeon>): mixed)|null $constraint Optional extra constraint applied to the base query.
     */
    protected function getDungeonWithNonFacadeFloor(?Closure $constraint = null): Dungeon
    {
        return $this->findDungeon(facadeEnabled: false, constraint: $constraint)[0];
    }

    /**
     * Returns a random dungeon guaranteed to have a current mapping version with facade rendering
     * disabled and EXACTLY one active non-facade floor. Use this instead of getDungeonWithNonFacadeFloor()
     * when a test seeds a single thumbnail row and asserts on freshness/count, since several seeded
     * dungeons (e.g. Karazhan) have many non-facade floors and would make such an assertion flaky.
     *
     * @param (Closure(Builder<Dungeon>): mixed)|null $constraint Optional extra constraint applied to the base query.
     */
    protected function getDungeonWithExactlyOneNonFacadeFloor(?Closure $constraint = null): Dungeon
    {
        return $this->findDungeon(
            facadeEnabled:   false,
            minActiveFloors: 1,
            maxActiveFloors: 1,
            constraint:      $constraint,
        )[0];
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
        return $this->findDungeon(facadeEnabled: false, minActiveFloors: 2, constraint: $constraint)[0];
    }

    /**
     * Returns a random dungeon guaranteed to have a current mapping version with facade rendering
     * enabled and at least one active facade floor. Use this when a test needs to exercise the
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
        return $this->findDungeon(facadeEnabled: true, constraint: $constraint)[0];
    }

    /**
     * Returns a Mythic+ dungeon whose current mapping version actually carries enemies.
     *
     * Deliberately deterministic: this returns the same dungeon on every run, as the fixture it
     * replaced did. Its callers have therefore only ever been exercised against one dungeon, and
     * randomising them here would surface latent per-dungeon assumptions as failures that look
     * indistinguishable from a regression. That is worth doing - but as its own change.
     */
    protected function getDungeonWithCurrentMappingVersionWithEnemies(?GameVersion $gameVersion = null): Dungeon
    {
        // Defaults to Retail, mirroring the guarantee the deleted DungeonFixtures fixture enforced -
        // a null $gameVersion here means "no requirement", not "Retail", which would silently drop it.
        $gameVersion ??= GameVersion::getDefaultGameVersion();

        return $this->findDungeon(
            challengeMode: true,
            minEnemies:    1,
            gameVersion:   $gameVersion,
            shuffle:       false,
            constraint:    static fn(Builder $query) => $query
                ->where('challenge_mode_id', '>', 0)
                ->orderByDesc('dungeons.id'),
        )[0];
    }

    /**
     * Loads the chosen dungeon on its own, so callers get a model that is exempt from
     * `preventLazyLoading()` just as the old `inRandomOrder()->first()` pick was.
     */
    private function reloadDungeon(Dungeon $dungeon): Dungeon
    {
        return Dungeon::query()->disableCache()->findOrFail($dungeon->id);
    }

    /**
     * Explains which requirement emptied the candidate pool, so a seed-data change reports what it
     * broke instead of just "no dungeon found".
     *
     * @param array<int, string> $requirements
     * @param array<string, int> $rejections
     */
    private function describeUnsatisfiableDungeon(array $requirements, int $candidateCount, array $rejections): string
    {
        $rejected = [];
        foreach ($rejections as $requirement => $count) {
            $rejected[] = sprintf('%s: %d', $requirement, $count);
        }

        return sprintf(
            'No seeded dungeon satisfies [%s]. %d dungeon(s) matched the SQL criteria%s.',
            implode(', ', $requirements),
            $candidateCount,
            $rejected === []
                ? ' and none were rejected during the scan, so the SQL criteria themselves match nothing'
                : sprintf(', all rejected during the scan (%s)', implode(', ', $rejected)),
        );
    }
}
