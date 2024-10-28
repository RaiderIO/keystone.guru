<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteChange;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait ChangesDungeonRoute
{
    private const IGNORE_KEYS = ['updated_at'];

    /**
     * @param DungeonRoute $dungeonRoute
     * @param Model|null   $beforeModel
     * @param Model|null   $afterModel
     * @throws Exception
     */
    public function dungeonRouteChanged(DungeonRoute $dungeonRoute, ?Model $beforeModel, ?Model $afterModel): void
    {
        if ($beforeModel === null && $afterModel === null) {
            throw new Exception('Must have at least a $beforeModel OR $afterModel');
        }

        $user = Auth::user();

        $boolToInt        = fn($value) => is_bool($value) ? (int)$value : $value;
        $beforeAttributes = $beforeModel !== null ? array_map($boolToInt, $beforeModel->getAttributes()) : [];
        $afterAttributes  = $afterModel !== null ? array_map($boolToInt, $afterModel->getAttributes()) : [];

        $changedKeys = $this->getChangedData($beforeAttributes, $afterAttributes, self::IGNORE_KEYS);

        DungeonRouteChange::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'user_id'          => $user?->id,
            'team_id'          => $dungeonRoute->team_id,
            'team_role'        => $dungeonRoute->team?->getUserRole($user),
            'model_id'         => $beforeModel?->id ?? $afterModel->id,
            'model_class'      => ($beforeModel ?? $afterModel)::class,
            'before'           => $beforeModel !== null ? json_encode(array_intersect_key($beforeAttributes, $changedKeys)) : null,
            'after'            => $afterModel !== null ? json_encode(array_intersect_key($afterAttributes, $changedKeys)) : null,
        ]);
    }

    private function getChangedData(array $before, array $after, array $excludeKeys = []): array
    {
        $alteredKeys = [];
        foreach ($before as $key => $value) {
            if (in_array($key, $excludeKeys)) {
                continue;
            }

            if ($value !== $after[$key]) {
                $alteredKeys[$key] = [
                    'before' => $value,
                    'after'  => $after[$key],
                ];
            }
        }

        return $alteredKeys;
    }
}
