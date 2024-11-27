<?php

namespace App\Http\Resources\DungeonRouteThumbnailJob;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Queue;
use JsonSerializable;

/**
 * @OA\Schema(schema="RouteThumbnailJobLinks")
 * @OA\Property(type="string",example="https://keystone.guru/api/v1/thumbnailJob/1",property="status")
 * @OA\Property(type="string",example="https://keystone.guru/images/route_thumbnails_custom/MS4cR1S_1.jpg",property="result")
 *
 * @mixin DungeonRouteThumbnailJob
 */
class DungeonRouteThumbnailJobLinksResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        $isCompleted = $this->status === DungeonRouteThumbnailJob::STATUS_COMPLETED;

        return [
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
        ];
    }
}
