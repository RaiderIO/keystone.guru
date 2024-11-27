<?php

namespace App\Http\Resources\DungeonRouteThumbnailJob;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Queue;
use JsonSerializable;

/**
 * @OA\Schema(schema="RouteThumbnailJob")
 * @OA\Property(type="integer",example="69",property="id")
 * @OA\Property(type="string",example="MS4cR1S",property="publicKey")
 * @OA\Property(type="integer",example="1",property="floorIndex",description="If the dungeon supports combined floors, the highest floor_index represents the floor with all combined floors.")
 * @OA\Property(type="integer",enum={"queued", "completed", "expired", "error"},property="status")
 * @OA\Property(type="integer",example="900",property="viewportWidth")
 * @OA\Property(type="integer",example="600",property="viewportHeight")
 * @OA\Property(type="integer",example="900",property="imageWidth")
 * @OA\Property(type="integer",example="600",property="imageHeight")
 * @OA\Property(type="number",format="float",example="2.2",property="zoomLevel")
 * @OA\Property(type="integer",example="90",property="quality")
 * @OA\Property(type="integer",example="493",property="queueSize")
 * @OA\Property(type="string",example="2024-01-25T20:22:14.000000Z",property="estimatedCompletion")
 * @OA\Property(type="string",example="2025-01-25T20:22:14.000000Z",property="expiresAt")
 * @OA\Property(ref="#/components/schemas/RouteThumbnailJobLinks",property="links")
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
            'links'               => (new DungeonRouteThumbnailJobLinksResource($this))->toArray($request),
        ];
    }
}
