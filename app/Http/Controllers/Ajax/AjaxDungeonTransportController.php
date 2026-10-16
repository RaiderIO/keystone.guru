<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\DungeonTransport\DungeonTransportChangedEvent;
use App\Events\Models\DungeonTransport\DungeonTransportDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Requests\DungeonTransport\DungeonTransportFormRequest;
use App\Models\DungeonTransport;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Repositories\Interfaces\DungeonTransportRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxDungeonTransportController extends AjaxMappingModelBaseController
{
    /**
     * @throws Throwable
     */
    public function store(
        DungeonTransportFormRequest $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        ?DungeonTransport           $dungeonTransport = null,
    ): DungeonTransport|Model {
        return $this->storeModel($coordinatesService, $mappingVersion, $request->validated(), DungeonTransport::class, $dungeonTransport);
    }

    public function delete(
        Request                             $request,
        DungeonTransportRepositoryInterface $dungeonTransportRepository,
        MappingVersion                      $mappingVersion,
        DungeonTransport                    $dungeonTransport,
    ): Response {
        try {
            $dungeon = $dungeonTransport->floor->dungeon;
            if ($dungeonTransport->delete()) {
                $dungeonTransportRepository->unlinkTransportsLinkedTo($dungeonTransport->id);

                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new DungeonTransportDeletedEvent($dungeon, $user, $dungeonTransport));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($dungeonTransport, null);
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
        return new DungeonTransportChangedEvent($context, $user, $model);
    }
}
