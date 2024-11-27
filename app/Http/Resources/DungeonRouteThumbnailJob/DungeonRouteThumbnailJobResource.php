<?php

namespace App\Http\Resources\DungeonRouteThumbnailJob;

use App\Http\Models\Response\RouteThumbnailJob\RouteThumbnailJobResponseModel;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Queue;
use JsonSerializable;

/**
 * Class DungeonRouteThumbnailJobResource
 *
 * @author Wouter
 *
 * @since 20/01/2024
 *
 * @mixin DungeonRouteThumbnailJob
 */
class DungeonRouteThumbnailJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        $queueSize = Queue::size(sprintf('%s-%s-thumbnail-api', config('app.type'), config('app.env')));

        $isCompleted = $this->status === DungeonRouteThumbnailJob::STATUS_COMPLETED;

        return [
            'id'                  => $this->id,
            'publicKey'           => $this->dungeonRoute->public_key,
            'floorIndex'          => $this->floor->index,
            'status'              => $this->status,
            'viewportWidth'       => $this->viewport_width ?? config('keystoneguru.api.dungeon_route.thumbnail.default_viewport_width'),
            'viewportHeight'      => $this->viewport_height ?? config('keystoneguru.api.dungeon_route.thumbnail.default_viewport_height'),
            'imageWidth'          => $this->image_width ?? config('keystoneguru.api.dungeon_route.thumbnail.default_image_width'),
            'imageHeight'         => $this->image_height ?? config('keystoneguru.api.dungeon_route.thumbnail.default_image_height'),
            'zoomLevel'           => $this->zoom_level ?? config('keystoneguru.api.dungeon_route.thumbnail.default_zoom_level'),
            'quality'             => $this->quality ?? config('keystoneguru.api.dungeon_route.thumbnail.default_quality'),
            'queueSize'           => $queueSize,
            'estimatedCompletion' => $isCompleted ? null : $this->created_at->addSeconds($queueSize * config('keystoneguru.api.dungeon_route.thumbnail.estimated_generation_time_seconds')),
            'expiresAt'           => $this->created_at->addSeconds(config('keystoneguru.api.dungeon_route.thumbnail.expiration_time_seconds')),
            'links'               => [
                'status' => route('api.v1.thumbnailjob.get', ['dungeonRouteThumbnailJob' => $this]),
                'result' => $isCompleted
                    ? url(
                        sprintf(
                            '%s/%s',
                            ThumbnailService::THUMBNAIL_CUSTOM_FOLDER_PATH,
                            ThumbnailService::getFilename($this->dungeonRoute, $this->floor->index)
                        )
                    )
                    : null,
            ],
        ];
    }
}
