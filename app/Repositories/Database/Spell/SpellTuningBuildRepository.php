<?php

namespace App\Repositories\Database\Spell;

use App\Models\Dungeon;
use App\Models\Spell\SpellDungeon;
use App\Models\Spell\SpellTuningBuild;
use App\Models\Spell\SpellTuningChange;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Spell\SpellTuningBuildRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class SpellTuningBuildRepository extends DatabaseRepository implements SpellTuningBuildRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(SpellTuningBuild::class);
    }

    public function getBuilds(int $gameVersionId, ?Dungeon $dungeon, int $perPage): LengthAwarePaginator
    {
        $spellCount = SpellTuningChange::query()
            ->selectRaw('COUNT(DISTINCT spell_tuning_changes.spell_id)')
            ->whereColumn('spell_tuning_changes.game_version_id', 'spell_tuning_builds.game_version_id')
            ->whereColumn('spell_tuning_changes.to_build', 'spell_tuning_builds.to_build');

        if ($dungeon !== null) {
            // The same pivot the spell index filters on, so both pages agree on which spells a dungeon has
            $spellCount->whereIn(
                'spell_tuning_changes.spell_id',
                SpellDungeon::query()->select('spell_id')->where('dungeon_id', $dungeon->id),
            );
        }

        return SpellTuningBuild::query()
            ->select(['from_build', 'to_build', 'to_build_number', 'to_build_released_at'])
            ->selectSub($spellCount, 'spell_count')
            ->where('game_version_id', $gameVersionId)
            ->orderByDesc('to_build_number')
            ->paginate($perPage)
            ->through(static fn(SpellTuningBuild $build): array => [
                'from_build'           => $build->from_build,
                'to_build'             => $build->to_build,
                'to_build_number'      => $build->to_build_number,
                'to_build_released_at' => $build->to_build_released_at,
                'spell_count'          => (int)$build->getAttribute('spell_count'),
            ]);
    }

    public function findReleasedAt(int $gameVersionId, string $toBuild): ?Carbon
    {
        return SpellTuningBuild::query()
            ->where('game_version_id', $gameVersionId)
            ->where('to_build', $toBuild)
            ->first()
            ?->to_build_released_at;
    }

    public function record(int $gameVersionId, string $fromBuild, string $toBuild, int $toBuildNumber, ?Carbon $toBuildReleasedAt): SpellTuningBuild
    {
        $values = [
            'from_build'      => $fromBuild,
            'to_build_number' => $toBuildNumber,
        ];

        if ($toBuildReleasedAt !== null) {
            $values['to_build_released_at'] = $toBuildReleasedAt->toDateTimeString();
        }

        return SpellTuningBuild::query()->updateOrCreate(
            ['game_version_id' => $gameVersionId, 'to_build' => $toBuild],
            $values,
        );
    }
}
