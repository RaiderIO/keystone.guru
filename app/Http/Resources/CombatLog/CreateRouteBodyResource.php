<?php

namespace App\Http\Resources\CombatLog;

use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * Class CreateRouteBodyResource
 *
 * @author Wouter
 *
 * @since 23/06/2024
 *
 * @mixin CreateRouteBody
 */
class CreateRouteBodyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        // All the properties in the CreateRouteBody class are public, so we can just cast it to an array
        return (array)$this;
    }
}
