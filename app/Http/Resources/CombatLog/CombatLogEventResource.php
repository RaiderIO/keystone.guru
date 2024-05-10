<?php

namespace App\Http\Resources\CombatLog;

use App\Http\Resources\AffixGroup\AffixGroupCollectionResource;
use App\Http\Resources\UserResource;
use App\Models\CombatLog\CombatLogEvent;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * Class CombatLogEventResource
 *
 * @author Wouter
 *
 * @since 02/05/2024
 *
 * @mixin CombatLogEvent
 */
class CombatLogEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        return $this->openSearchArray();
    }
}
