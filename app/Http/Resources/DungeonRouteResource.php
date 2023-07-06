<?php

namespace App\Http\Resources;

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
        return [
            'public_key'            => $this->public_key,
            'dungeon'               => __($this->dungeon->name, [], 'en'),
            'pulls'                 => $this->killZones()->count(),
            'enemy_forces'          => $this->enemy_forces,
            'enemy_forces_required' => $this->dungeon->getCurrentMappingVersion()->enemy_forces_required,
            'expires_at'            => $this->expires_at,
        ];
    }
}
