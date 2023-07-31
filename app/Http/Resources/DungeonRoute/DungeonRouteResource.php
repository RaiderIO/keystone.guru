<?php

namespace App\Http\Resources\DungeonRoute;

use App\Http\Resources\AffixGroup\AffixGroupCollectionResource;
use App\Http\Resources\UserResource;
use App\Models\DungeonRoute;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * Class DungeonRouteResource
 *
 * @package App\Http\Resources
 * @author Wouter
 * @since 12/06/2023
 * @mixin DungeonRoute
 */
class DungeonRouteResource extends JsonResource
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
        $thumbnailUrls = [];
        foreach ($this->dungeon->floors as $floor) {
            $thumbnailUrls[] = $this->getThumbnailUrl($floor);
        }

        $dungeonRouteUrlParams = ['dungeon' => $this->dungeon, 'dungeonroute' => $this, 'title' => $this->getTitleSlug()];

        return [
            'public_key'            => $this->public_key,
            'title'                 => $this->title,
            'dungeon'               => $this->dungeon->slug,
            'pulls'                 => $this->killZones->count(),
            'enemy_forces'          => $this->enemy_forces,
            'enemy_forces_required' => $this->dungeon->getCurrentMappingVersion()->enemy_forces_required,
            'expires_at'            => $this->expires_at,
            'author'                => new UserResource($this->author),
            'affix_groups'          => new AffixGroupCollectionResource($this->affixes),
            'links'                 => [
                'view'       => route('dungeonroute.view', $dungeonRouteUrlParams),
                'edit'       => route('dungeonroute.edit', $dungeonRouteUrlParams),
                'embed'      => route('dungeonroute.embed', $dungeonRouteUrlParams),
                'thumbnails' => $thumbnailUrls
            ]
        ];
    }
}
