<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\DungeonStart\DungeonStartChangedEvent;
use App\Events\Models\DungeonStart\DungeonStartDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Requests\DungeonStart\DungeonStartFormRequest;
use App\Models\DungeonStart;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxDungeonStartController extends AjaxMappingModelBaseController
{
    /**
     * @throws Throwable
     */
    public function store(
        DungeonStartFormRequest     $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        ?DungeonStart               $dungeonStart = null,
    ): DungeonStart|Model {
        return $this->storeModel($coordinatesService, $mappingVersion, $request->validated(), DungeonStart::class, $dungeonStart);
    }

    public function delete(
        Request        $request,
        MappingVersion $mappingVersion,
        DungeonStart   $dungeonStart,
    ): Response {
        try {
            $dungeon = $dungeonStart->floor->dungeon;
            if ($dungeonStart->delete()) {
                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new DungeonStartDeletedEvent($dungeon, $user, $dungeonStart));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($dungeonStart, null);
            }

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Model                       $model,
    ): ModelChangedEvent {
        return new DungeonStartChangedEvent($context, $user, $model);
    }
}
