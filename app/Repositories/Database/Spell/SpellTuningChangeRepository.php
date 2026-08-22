<?php

namespace App\Repositories\Database\Spell;

use App\Models\Dungeon;
use App\Models\Spell\SpellDungeon;
use App\Models\Spell\SpellTuningChange;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Spell\SpellTuningChangeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SpellTuningChangeRepository extends DatabaseRepository implements SpellTuningChangeRepositoryInterface
{
    private const int INSERT_CHUNK_SIZE = 500;

    public function __construct()
    {
        parent::__construct(SpellTuningChange::class);
    }

    public function getForSpell(int $spellId): Collection
    {
        return SpellTuningChange::query()
            ->where('spell_id', $spellId)
            ->orderByDesc('to_build_number')
            ->orderBy('value_index')
            ->orderBy('id')
            ->get();
    }

    public function getBuilds(int $gameVersionId, ?Dungeon $dungeon, int $perPage): LengthAwarePaginator
    {
        return $this->scopeToDungeon(SpellTuningChange::query(), $dungeon)
            ->select(['from_build', 'to_build', 'to_build_number'])
            ->selectRaw('COUNT(DISTINCT spell_id) AS spell_count')
            ->where('game_version_id', $gameVersionId)
            ->groupBy(['from_build', 'to_build', 'to_build_number'])
            ->orderByDesc('to_build_number')
            ->paginate($perPage)
            ->through(static fn(SpellTuningChange $row): array => [
                'from_build'      => $row->from_build,
                'to_build'        => $row->to_build,
                'to_build_number' => $row->to_build_number,
                'spell_count'     => (int)$row->getAttribute('spell_count'),
            ]);
    }

    public function getForBuild(int $gameVersionId, string $toBuild, ?Dungeon $dungeon): Collection
    {
        return $this->scopeToDungeon(SpellTuningChange::query(), $dungeon)
            ->with(['spell', 'spell.dungeons'])
            ->where('game_version_id', $gameVersionId)
            ->where('to_build', $toBuild)
            // Biggest swings first; rewritten descriptions and non-scalable changes (no delta) after them
            ->orderByRaw('delta IS NULL, ABS(delta) DESC')
            ->orderBy('spell_id')
            ->orderBy('value_index')
            ->get();
    }

    public function replaceForBuild(int $gameVersionId, string $toBuild, array $rows): int
    {
        $inserted = DB::transaction(static function () use ($gameVersionId, $toBuild, $rows): int {
            SpellTuningChange::query()
                ->where('game_version_id', $gameVersionId)
                ->where('to_build', $toBuild)
                ->delete();

            $inserted = 0;
            foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
                SpellTuningChange::query()->insert($chunk);
                $inserted += count($chunk);
            }

            return $inserted;
        });

        // insert()/delete() go around the model events that keep the model cache in step
        new SpellTuningChange()->flushCache();

        return $inserted;
    }

    /**
     * @param  Builder<SpellTuningChange> $builder
     * @return Builder<SpellTuningChange>
     */
    private function scopeToDungeon(Builder $builder, ?Dungeon $dungeon): Builder
    {
        if ($dungeon === null) {
            return $builder;
        }

        // The same pivot the spell index filters on, so both pages agree on which spells a dungeon has
        return $builder->whereIn(
            'spell_id',
            SpellDungeon::query()->select('spell_id')->where('dungeon_id', $dungeon->id),
        );
    }
}
