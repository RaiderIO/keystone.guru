<?php

namespace App\Http\Resources\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="DungeonRouteLinks")
 * @OA\Property(property="view", type="string", example="https://keystone.guru/route/ara-kara-city-of-echoes/01ta7yg/ara-kara-city-of-echoes")
 * @OA\Property(property="edit", type="string", example="https://keystone.guru/route/ara-kara-city-of-echoes/01ta7yg/ara-kara-city-of-echoes/edit")
 * @OA\Property(property="embed", type="string", example="https://keystone.guru/route/ara-kara-city-of-echoes/01ta7yg/ara-kara-city-of-echoes/embed")
 * @OA\Property(property="thumbnails", type="array", @OA\Items(type="string", example="https://uploads.keystone.guru/route_thumbnails/01ta7yg_1.jpg"))
 *
 * @mixin DungeonRoute
 */
class DungeonRouteLinksResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        $thumbnailUrls = [];
        foreach($this->thumbnails as $thumbnail) {
            $thumbnailUrls[] = $thumbnail->getURL();
        }

        $dungeonRouteUrlParams = ['dungeon' => $this->dungeon, 'dungeonroute' => $this, 'title' => $this->getTitleSlug()];

        return [
            'view'       => route('dungeonroute.view', $dungeonRouteUrlParams),
            'edit'       => route('dungeonroute.edit', $dungeonRouteUrlParams),
            'embed'      => route('dungeonroute.embed', $dungeonRouteUrlParams),
            'thumbnails' => $thumbnailUrls,
        ];
    }
}
