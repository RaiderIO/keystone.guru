<?php

namespace App\Repositories\Database\Spell;

use App\Models\Spell\SpellTuningBuild;
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

    public function getBuilds(int $gameVersionId, int $perPage): LengthAwarePaginator
    {
        return SpellTuningBuild::query()
            ->where('game_version_id', $gameVersionId)
            ->orderByDesc('to_build_number')
            ->paginate($perPage);
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
