<?php

namespace App\Http\Resources\DungeonRoute;

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
 * @package App\Http\Resources
 * @author Wouter
 * @since 20/01/2024
 * @mixin DungeonRouteThumbnailJob
 */
class DungeonRouteThumbnailJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $queueSize = Queue::size(sprintf('%s-%s-thumbnail-api', config('app.type'), config('app.env')));

        return [
            'id'                   => $this->id,
            'public_key'           => $this->dungeonRoute->public_key,
            'floor_index'          => $this->floor->index,
            'status'               => $this->status,
            'width'                => $this->width ?? config('keystoneguru.api.dungeon_route.thumbnail.default_width'),
            'height'               => $this->height ?? config('keystoneguru.api.dungeon_route.thumbnail.default_height'),
            'quality'              => $this->quality ?? config('keystoneguru.api.dungeon_route.thumbnail.default_quality'),
            'queue_size'           => $queueSize,
            'estimated_completion' => $this->created_at->addSeconds($queueSize * config('keystoneguru.api.dungeon_route.thumbnail.estimated_generation_time_seconds')),
            'expires_at'           => $this->created_at->addSeconds(config('keystoneguru.api.dungeon_route.thumbnail.expiration_time_seconds')),
            'links'                => [
//                'result' => $this->status === self::STATUS_COMPLETED ? public_path(ThumbnailService::THUMBNAIL_CUSTOM_FOLDER_PATH),
            ],
        ];
    }
}
