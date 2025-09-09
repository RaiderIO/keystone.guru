<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Enemy\EnemyChangedEvent;
use App\Events\Models\Enemy\EnemyDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Requests\Enemy\APIEnemyFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\EnemyActiveAura;
use App\Models\Mapping\MappingVersion;
use App\Models\RaidMarker;
use App\Models\Spell\Spell;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
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

class AjaxEnemyController extends AjaxMappingModelBaseController
{
    /**
     * @param APIEnemyFormRequest         $request
     * @param CoordinatesServiceInterface $coordinatesService
     * @param MappingVersion              $mappingVersion
     * @param Enemy|null                  $enemy
     * @return Enemy|Model
     *
     * @throws Throwable
     */
    public function store(
        APIEnemyFormRequest         $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        ?Enemy                      $enemy = null
    ): Enemy|Model {
        $validated = $request->validated();

        $previousFloor = null;
        if ($enemy !== null) {
            // Load the enemy from database - don't use the given enemy's floor since that'll be the new floor potentially
            /** @var Enemy|null $previousEnemy */
            $previousEnemy = optional(Enemy::with(['floor'])->find($enemy->id));
            $previousFloor = $previousEnemy->floor;
        }

        $validated['kill_priority'] = in_array((int)$validated['kill_priority'], [
            0,
            -1,
        ]) ? null : (int)$validated['kill_priority'];

        return $this->storeModel($coordinatesService, $mappingVersion, $validated, Enemy::class, $enemy, static function (
            Enemy $enemy
        ) use ($request, $coordinatesService, $previousFloor) {
            $activeAuras = $request->get('active_auras', []);
            // Clear current active auras
            $enemy->enemyActiveAuras()->delete();
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

            $enemy->load([
                'npc',
                'npc.enemyForces',
                'floor',
            ])->makeHidden(['floor']);
            // Perform floor change and move enemy to the correct location on the new floor
            if ($previousFloor !== null && $enemy->floor->id !== $previousFloor->id) {
                $ingameXY  = $coordinatesService->calculateIngameLocationForMapLocation($enemy->getLatLng()->setFloor($previousFloor));
                $newLatLng = $coordinatesService->calculateMapLocationForIngameLocation($ingameXY->setFloor($enemy->floor));

                $enemy->update($newLatLng->toArray());
            }

            $enemy->npc->name = __($enemy->npc->name);
            foreach ($enemy->npc->spells as $spell) {
                $spell->name           = __($spell->name);
                $spell->category       = __($spell->category);
                $spell->cooldown_group = __($spell->cooldown_group);
            }
        });
    }

    /**
     * @return array|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function setRaidMarker(Request $request, DungeonRoute $dungeonRoute, Enemy $enemy)
    {
        $this->authorize('edit', $dungeonRoute);

        try {
            $raidMarkerName = $request->get('raid_marker_name', '');

            // Delete existing enemy raid marker
            DungeonRouteEnemyRaidMarker::where('enemy_id', $enemy->id)->where('dungeon_route_id', $dungeonRoute->id)->delete();

            // Create a new one, if the user didn't just want to clear it
            if (!empty($raidMarkerName)) {
                DungeonRouteEnemyRaidMarker::create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'raid_marker_id'   => RaidMarker::ALL[$raidMarkerName],
                    'enemy_id'         => $enemy->id,
                ]);

                $result = ['name' => $raidMarkerName];
            } else {
                $result = ['name' => ''];
            }

        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @return Response
     *
     * @throws Throwable
     */
    public function delete(
        Request        $request,
        MappingVersion $mappingVersion,
        Enemy          $enemy
    ): Response {
        return DB::transaction(function () use ($enemy) {
            try {
                if ($enemy->delete()) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($enemy, null);

                    if (Auth::check()) {
                        /** @var User $user */
                        $user = Auth::getUser();
                        broadcast(new EnemyDeletedEvent($enemy->floor->dungeon, $user, $enemy));
                    }
                }

                $result = response()->noContent();
            } catch (Exception) {
                $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
            }

            return $result;
        });
    }

    protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Model                       $model
    ): ModelChangedEvent {
        return new EnemyChangedEvent($coordinatesService, $context, $user, $model);
    }
}
