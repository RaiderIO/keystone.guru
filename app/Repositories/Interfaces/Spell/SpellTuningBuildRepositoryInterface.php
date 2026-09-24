<?php

namespace App\Repositories\Interfaces\Spell;

use App\Models\Dungeon;
use App\Models\Spell\SpellTuningBuild;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method SpellTuningBuild                  create(array<string, mixed> $attributes)
 * @method SpellTuningBuild|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellTuningBuild                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellTuningBuild                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(SpellTuningBuild $model)
 * @method bool                              update(SpellTuningBuild $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(SpellTuningBuild $model)
 * @method Collection<int, SpellTuningBuild> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface SpellTuningBuildRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Every compared build of a game version, newest first, including builds without changes. Each page
     * item is `{from_build, to_build, to_build_number, to_build_released_at, spell_count}`, where
     * `spell_count` optionally only counts spells linked to a dungeon.
     *
     * @return LengthAwarePaginator<int, covariant array{from_build: string, to_build: string, to_build_number: int, to_build_released_at: Carbon|null, spell_count: int}>
     */
    public function getBuilds(int $gameVersionId, ?Dungeon $dungeon, int $perPage): LengthAwarePaginator;

    /**
     * When the given build went live, as recorded for it, or null when it is unknown or carries no date.
     */
    public function findReleasedAt(int $gameVersionId, string $toBuild): ?Carbon;

    /**
     * Records that $toBuild was compared with $fromBuild, replacing an earlier record of the same build.
     * A null $toBuildReleasedAt keeps the date already recorded for the build.
     */
    public function record(int $gameVersionId, string $fromBuild, string $toBuild, int $toBuildNumber, ?Carbon $toBuildReleasedAt): SpellTuningBuild;
}
