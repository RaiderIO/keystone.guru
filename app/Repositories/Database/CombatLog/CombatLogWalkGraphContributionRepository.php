<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogWalkGraphContribution;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogWalkGraphContributionRepositoryInterface;
use Illuminate\Support\Collection;

class CombatLogWalkGraphContributionRepository extends DatabaseRepository implements CombatLogWalkGraphContributionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogWalkGraphContribution::class);
    }

    public function getUiMapIds(): Collection
    {
        return CombatLogWalkGraphContribution::query()
            ->distinct()
            ->orderBy('ui_map_id')
            ->pluck('ui_map_id')
            ->map(static fn($uiMapId) => (int)$uiMapId)
            ->values();
    }

    public function getLatestId(int $uiMapId): ?int
    {
        $latestId = CombatLogWalkGraphContribution::query()->where('ui_map_id', $uiMapId)->max('id');

        return $latestId === null ? null : (int)$latestId;
    }

    public function getRecentRunKeys(int $uiMapId, int $limit): Collection
    {
        return CombatLogWalkGraphContribution::query()
            ->where('ui_map_id', $uiMapId)
            ->groupBy('run_key')
            ->selectRaw('run_key, MAX(id) as last_id')
            ->orderByDesc('last_id')
            ->limit($limit)
            ->pluck('run_key')
            ->map(static fn($runKey) => (string)$runKey)
            ->values();
    }

    public function getEdgesForRunKeys(int $uiMapId, Collection $runKeys): Collection
    {
        if ($runKeys->isEmpty()) {
            return collect();
        }

        return CombatLogWalkGraphContribution::query()
            ->where('ui_map_id', $uiMapId)
            ->whereIn('run_key', $runKeys)
            ->get(['run_key', 'edges'])
            ->toBase();
    }

    public function pruneOutsideRunKeys(int $uiMapId, Collection $keptRunKeys, int $upToId): int
    {
        return CombatLogWalkGraphContribution::query()
            ->where('ui_map_id', $uiMapId)
            ->where('id', '<=', $upToId)
            ->when($keptRunKeys->isNotEmpty(), static fn($query) => $query->whereNotIn('run_key', $keptRunKeys))
            ->toBase()
            ->delete();
    }
}
