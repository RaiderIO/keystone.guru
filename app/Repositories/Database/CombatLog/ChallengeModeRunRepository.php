<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\ChallengeModeRun;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\ChallengeModeRunRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChallengeModeRunRepository extends DatabaseRepository implements ChallengeModeRunRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(ChallengeModeRun::class);
    }

    public function getByDungeonRouteIds(Collection $dungeonRouteIds, ?Collection $periods = null): Collection
    {
        /** @var Collection<int, ChallengeModeRun> $result */
        $result = ChallengeModeRun::query()
            ->whereIn('dungeon_route_id', $dungeonRouteIds)
            ->when($periods !== null, static fn(Builder $builder) => $builder->whereHas(
                'challengeModeRunData',
                static fn(Builder $builder) => $builder->whereIn(
                    // The week a run belongs to only lives inside the stored request body - and a run whose
                    // post_body was pruned cannot be regenerated at all, so dropping it here costs nothing
                    DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(post_body, '$.metadata.period')) AS UNSIGNED)"),
                    $periods,
                ),
            ))
            ->get();

        return $result;
    }
}
