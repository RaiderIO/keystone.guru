<?php

namespace App\Http\Resources\DungeonRouteThumbnailJob;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="RouteThumbnailJobLinks")
 * @OA\Property(type="string",property="status",example="https://keystone.guru/api/v1/thumbnailJob/1")
 * @OA\Property(type="string",property="result",example="https://uploads.keystone.guru/thumbnails_custom/MS4cR1S/1.jpg")
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
                        ThumbnailService::getFilename($this->dungeonRoute, $this->floor->index),
                    ),
                )
                : null,
        ];
    }
}
