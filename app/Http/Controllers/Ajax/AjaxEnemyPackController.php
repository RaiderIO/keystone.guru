<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\EnemyPack\EnemyPackChangedEvent;
use App\Events\Models\EnemyPack\EnemyPackDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Requests\EnemyPack\EnemyPackFormRequest;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AjaxEnemyPackController extends AjaxMappingModelBaseController
{
    /**
     * @return EnemyPack|Model
     *
     * @throws Throwable
     */
    public function store(
        EnemyPackFormRequest $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion       $mappingVersion,
        ?EnemyPack           $enemyPack = null
    ): EnemyPack {
        $validated = $request->validated();

        return $this->storeModel($coordinatesService, $mappingVersion, $validated, EnemyPack::class, $enemyPack);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function delete(Request $request, MappingVersion $mappingVersion, EnemyPack $enemyPack): Response
    {
        return DB::transaction(function () use ($enemyPack) {
            if ($enemyPack->delete()) {
                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemyPack, null);

                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();
                    broadcast(new EnemyPackDeletedEvent($enemyPack->floor->dungeon, $user, $enemyPack));
                }

                $result = response()->noContent();
            } else {
                throw new Exception('Unable to delete pack!');
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
        return new EnemyPackChangedEvent($context, $user, $model);
    }
}
