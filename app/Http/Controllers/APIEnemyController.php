<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Controllers\Traits\PublicKeyDungeonRoute;
use App\Http\Requests\Enemy\EnemyFormRequest;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\EnemyActiveAura;
use App\Models\RaidMarker;
use App\Models\Spell;
use App\Service\Mapping\MappingService;
use DB;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class APIEnemyController extends APIMappingModelBaseController
{
    use PublicKeyDungeonRoute;
    use ChecksForDuplicates;

    /**
     * @param EnemyFormRequest $request
     * @param Enemy|null $enemy
     * @return Enemy|Model
     * @throws Exception
     * @throws Throwable
     */
    public function store(EnemyFormRequest $request, Enemy $enemy = null): Enemy
    {
        $validated = $request->validated();

        $validated['vertices_json'] = json_encode($request->get('vertices'));
        unset($validated['vertices']);

        return $this->storeModel($validated, Enemy::class, $enemy, function (Enemy $enemy) use ($request) {
            $activeAuras = $request->get('active_auras', []);
            // Clear current active auras
            $enemy->enemyactiveauras()->delete();
            foreach ($activeAuras as $activeAura) {
                if (!empty($activeAura)) {
                    $spell = Spell::findOrFail($activeAura);
                    // Only when the passed spell is actually an aura
                    if ($spell->aura) {
                        EnemyActiveAura::insert([
                            'enemy_id' => $enemy->id,
                            'spell_id' => $activeAura,
                        ]);
                    }
                }
            }

            $enemy->load(['npc']);
        });
    }

    /**
     * @param Request $request
     * @param DungeonRoute $dungeonroute
     * @param Enemy $enemy
     * @return array|ResponseFactory|Response
     * @throws AuthorizationException
     */
    public function setRaidMarker(Request $request, DungeonRoute $dungeonroute, Enemy $enemy)
    {
        $this->authorize('edit', $dungeonroute);

        try {
            $raidMarkerName = $request->get('raid_marker_name', '');

            // Delete existing enemy raid marker
            DungeonRouteEnemyRaidMarker::where('enemy_id', $enemy->id)->where('dungeon_route_id', $dungeonroute->id)->delete();

            // Create a new one, if the user didn't just want to clear it
            if (!empty($raidMarkerName)) {
                $raidMarker                   = new DungeonRouteEnemyRaidMarker();
                $raidMarker->dungeon_route_id = $dungeonroute->id;
                $raidMarker->raid_marker_id   = RaidMarker::where('name', $raidMarkerName)->first()->id;
                $raidMarker->enemy_id         = $enemy->id;
                $raidMarker->save();

                $result = ['name' => $raidMarkerName];
            } else {
                $result = ['name' => ''];
            }

        } catch (Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param Enemy $enemy
     * @return Response|ResponseFactory
     * @throws Throwable
     */
    public function delete(Request $request, Enemy $enemy)
    {
        return DB::transaction(function () use ($request, $enemy) {
            try {
                if ($enemy->delete()) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($enemy, null);

                    if (Auth::check()) {
                        broadcast(new ModelDeletedEvent($enemy->floor->dungeon, Auth::getUser(), $enemy));
                    }
                }
                $result = response()->noContent();
            } catch (Exception $ex) {
                $result = response('Not found', Http::NOT_FOUND);
            }

            return $result;
        });
    }
}
