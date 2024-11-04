<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteChange;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait ChangesDungeonRoute
{
    private const IGNORE_KEYS = [
        'index', // KillZone index upon mass change.. I don't care about this
        'updated_at',
    ];

    /**
     * @param DungeonRoute $dungeonRoute
     * @param Model|null   $beforeModel
     * @param Model|null   $afterModel
     * @throws Exception
     */
    public function dungeonRouteChanged(DungeonRoute $dungeonRoute, ?Model $beforeModel, ?Model $afterModel, ?callable $modifyAttributes = null): void
    {
        // We only care for these changes when the route is part of a team
        if ($dungeonRoute->team_id === null) {
            return;
        }

        if ($beforeModel === null && $afterModel === null) {
            throw new Exception('Must have at least a $beforeModel OR $afterModel');
        }

        $user = Auth::user();

        $boolToInt        = fn($value) => is_bool($value) ? (int)$value : $value;
        $beforeAttributes = $beforeModel !== null ? array_map($boolToInt, $beforeModel->getAttributes()) : [];
        $afterAttributes  = $afterModel !== null ? array_map($boolToInt, $afterModel->getAttributes()) : [];
        if ($modifyAttributes !== null) {
            $modifyAttributes($beforeAttributes, $afterAttributes);
        }

        $changedKeys = $this->getChangedData($beforeAttributes, $afterAttributes, self::IGNORE_KEYS);

        // If there are any changes, log them
        if (!empty($changedKeys)) {
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
    }

    private function getChangedData(array $before, array $after, array $excludeKeysOnUpdate = []): array
    {
        $alteredKeys = [];
        // Creating a new model
        if (empty($before)) {
            foreach ($after as $key => $value) {
                $alteredKeys[$key] = [
                    'before' => null,
                    'after'  => $value,
                ];
            }
        } // Deleting a model
        else if (empty($after)) {
            foreach ($before as $key => $value) {
                $alteredKeys[$key] = [
                    'before' => $value,
                    'after'  => null,
                ];
            }
        } // Updating a model
        else {
            foreach ($before as $key => $value) {
                if (in_array($key, $excludeKeysOnUpdate)) {
                    continue;
                }

                if ($value !== $after[$key]) {
                    $alteredKeys[$key] = [
                        'before' => $value,
                        'after'  => $after[$key],
                    ];
                }
            }
        }

        return $alteredKeys;
    }
}
