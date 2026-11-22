<?php

namespace App\Mcp\Tools\Route\Concerns;

use App\Http\Requests\KillZone\APIKillZoneMassFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Service\KillZone\KillZoneServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Renumbers a route's pulls 1..n in the given order the way the editor's mass save does: through
 * APIKillZoneMassFormRequest and KillZoneService, in one retried transaction.
 *
 * @property KillZoneServiceInterface $killZoneService
 */
trait SavesPullOrder
{
    /**
     * @param array<int, KillZone> $killZones kill zone id => kill zone, of the route
     * @param list<int>            $pullIds   every key of $killZones, in the new order
     *
     * @throws ValidationException
     */
    protected function savePullOrder(DungeonRoute $dungeonRoute, User $user, array $killZones, array $pullIds): void
    {
        if ($pullIds === []) {
            return;
        }

        $massData = $this->validateWithFormRequest(APIKillZoneMassFormRequest::class, $user, [
            'killzones' => array_map(static fn(int $pullId, int $position) => [
                'id'    => $pullId,
                'color' => $killZones[$pullId]->color,
                'index' => $position + 1,
            ], $pullIds, array_keys($pullIds)),
        ]);

        DB::transaction(function () use ($dungeonRoute, $massData): void {
            foreach ($massData['killzones'] as $killZoneData) {
                $this->killZoneService->saveKillZone($dungeonRoute, $killZoneData, false);
            }
        }, 3);
    }
}
