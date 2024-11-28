<?php

namespace App\Http\Resources\CombatLog\Route;

use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="CombatLogRouteRequestCorrection")
 * @OA\Property(property="metadata", ref="#/components/schemas/CombatLogRouteMetadata")
 * @OA\Property(property="settings", ref="#/components/schemas/CombatLogRouteSettings")
 * @OA\Property(property="challengeMode", ref="#/components/schemas/CombatLogRouteChallengeMode")
 * @OA\Property(property="npcs",type="array",items={"$ref":"#/components/schemas/CombatLogRouteNpcCorrection"})
 * @OA\Property(property="spells",type="array",items={"$ref":"#/components/schemas/CombatLogRouteSpell"}, nullable=true)
 *
 * @property CombatLogRouteRequestModel $resource
 *
 * @mixin CombatLogRouteRequestModel
 */
class CombatLogRouteRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}

