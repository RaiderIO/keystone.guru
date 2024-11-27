<?php

namespace App\Http\Resources\CombatLog;

use App\Http\Models\Request\CombatLog\Route\CombatLogRoute;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * Class CombatLogRouteResource
 *
 * @author Wouter
 *
 * @since 23/06/2024
 *
 * @mixin CombatLogRoute
 */
class CombatLogRouteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        // All the properties in the CombatLogRoute class are public, so we can just cast it to an array
        return (array)$this->resource;
    }
}
