<?php

namespace App\Repositories\Interfaces\Spell;

use App\Models\Dungeon;
use App\Models\Spell\SpellTuningChange;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @method SpellTuningChange                  create(array<string, mixed> $attributes)
 * @method SpellTuningChange|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellTuningChange                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellTuningChange                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                               save(SpellTuningChange $model)
 * @method bool                               update(SpellTuningChange $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                               delete(SpellTuningChange $model)
 * @method Collection<int, SpellTuningChange> all()
 * @method bool                               exists(array<int, string> $columns)
 */
interface SpellTuningChangeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Every recorded change of one spell, newest build first, values in description order within a build.
     *
     * @return Collection<int, SpellTuningChange>
     */
    public function getForSpell(int $spellId): Collection;

    /**
     * The builds that carry changes for a game version, newest first, optionally only counting spells
     * linked to a dungeon. Each page item is `{from_build, to_build, to_build_number, spell_count}`.
     *
     * @return LengthAwarePaginator<int, array{from_build: string, to_build: string, to_build_number: int, spell_count: int}>
     */
    public function getBuilds(int $gameVersionId, ?Dungeon $dungeon, int $perPage): LengthAwarePaginator;

    /**
     * Every change a build introduced, largest damage swing first, optionally only for spells linked to
     * a dungeon. Spells (with their dungeons) are eager loaded for the spell link and chips.
     *
     * @return Collection<int, SpellTuningChange>
     */
    public function getForBuild(int $gameVersionId, string $toBuild, ?Dungeon $dungeon): Collection;

    /**
     * Replaces every change recorded for the given build with $rows, so re-running the diff for the
     * same build pair is idempotent. Returns the number of rows inserted.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function replaceForBuild(int $gameVersionId, string $toBuild, array $rows): int;
}
