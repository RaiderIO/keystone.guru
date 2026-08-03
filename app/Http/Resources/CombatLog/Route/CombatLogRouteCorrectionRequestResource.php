<?php

namespace App\Http\Resources\CombatLog\Route;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @OA\Schema(schema="CombatLogRouteRequestCorrection")
 * @OA\Property(property="metadata",type="object",ref="#/components/schemas/CombatLogRouteMetadata")
 * @OA\Property(property="settings",type="object",ref="#/components/schemas/CombatLogRouteSettings")
 * @OA\Property(property="challengeMode",type="object",ref="#/components/schemas/CombatLogRouteChallengeMode")
 * @OA\Property(property="npcs",type="array",items={"$ref":"#/components/schemas/CombatLogRouteNpcCorrection"})
 * @OA\Property(property="spells",type="array",items={"$ref":"#/components/schemas/CombatLogRouteSpellCorrection"})
 * @OA\Property(property="playerDeaths",type="array",items={"$ref":"#/components/schemas/CombatLogRoutePlayerDeathCorrection"})
 *
 * @property CombatLogRouteRequestDTO $resource
 *
 * @mixin CombatLogRouteRequestDTO
 */
class CombatLogRouteCorrectionRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
