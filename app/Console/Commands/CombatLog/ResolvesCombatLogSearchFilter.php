<?php

namespace App\Console\Commands\CombatLog;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Shared filter-resolution logic for `combatlog:searchruns` and `combatlog:downloadruns`: turns the
 * `--dungeon`/`--class`/`--spec`/`--min-level`/`--from-days`/`--limit`/`--offset` options both commands
 * declare into a {@see SearchAdvancedRunsFilter} ready to hand to `RaiderIOApiServiceInterface::searchAdvancedRuns()`.
 *
 * @mixin Command
 */
trait ResolvesCombatLogSearchFilter
{
    /**
     * @throws RuntimeException When --dungeon does not resolve to exactly one Dungeon, or a --class key is unknown.
     */
    protected function buildSearchFilter(Season $season): SearchAdvancedRunsFilter
    {
        return new SearchAdvancedRunsFilter(
            dungeon:         $this->resolveDungeonOption(),
            season:          $season,
            specs:           $this->resolveSpecOptions(),
            completedAtFrom: Carbon::now()->subDays((int)$this->option('from-days')),
            completedAtTo:   null,
            mythicLevelMin:  (int)$this->option('min-level'),
            mythicLevelMax:  null,
            limit:           (int)$this->option('limit'),
            offset:          (int)$this->option('offset'),
        );
    }

    /**
     * Resolves --dungeon to a Dungeon, matching on `key` first and falling back to a translated-`name` search.
     * `name` is stored as a translation key (e.g. `dungeons.tww.the_rookery.name`), not display text, so the
     * fallback is done in PHP against `__($dungeon->name)` rather than a SQL `LIKE` on the raw column.
     *
     * @throws RuntimeException When the option is set but matches zero or more than one Dungeon.
     */
    protected function resolveDungeonOption(): ?Dungeon
    {
        $dungeonOption = $this->option('dungeon');

        if ($dungeonOption === null || $dungeonOption === '') {
            return null;
        }

        $dungeon = Dungeon::query()->where('key', $dungeonOption)->first();

        if ($dungeon !== null) {
            return $dungeon;
        }

        $matches = Dungeon::query()->get()->filter(
            static fn(Dungeon $candidate): bool => str_contains(
                Str::lower(__($candidate->name)),
                Str::lower($dungeonOption),
            ),
        );

        if ($matches->count() === 1) {
            /** @var Dungeon */
            return $matches->first();
        }

        if ($matches->count() > 1) {
            throw new RuntimeException(sprintf(
                'Ambiguous --dungeon "%s", matches: %s',
                $dungeonOption,
                $matches->map(static fn(Dungeon $candidate): string => __($candidate->name))->implode(', '),
            ));
        }

        throw new RuntimeException(sprintf('Unknown --dungeon "%s"', $dungeonOption));
    }

    /**
     * Builds the union of specs implied by --class (all specs of each resolved class) and --spec
     * (explicit CharacterClassSpecialization ids).
     *
     * @return Collection<int, CharacterClassSpecialization>
     * @throws RuntimeException                              When a --class key does not resolve to a known CharacterClass.
     */
    protected function resolveSpecOptions(): Collection
    {
        /** @var Collection<int, CharacterClassSpecialization> $specs */
        $specs = collect();

        foreach ((array)$this->option('class') as $classKey) {
            $class = CharacterClass::query()->where('key', $classKey)->first();

            if ($class === null) {
                throw new RuntimeException(sprintf(
                    'Unknown --class "%s", expected one of: %s',
                    $classKey,
                    implode(', ', CharacterClass::ALL),
                ));
            }

            $specs = $specs->merge($class->specializations);
        }

        $specIds = array_map(intval(...), (array)$this->option('spec'));

        if (!empty($specIds)) {
            $specs = $specs->merge(CharacterClassSpecialization::query()->whereIn('id', $specIds)->get());
        }

        /** @var Collection<int, CharacterClassSpecialization> */
        return $specs->unique('id')->values();
    }
}
