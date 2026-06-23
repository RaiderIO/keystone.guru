<?php

namespace App\Http\Resources\CombatLog;

use App\Models\CombatLog\CombatLogEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

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
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return $this->openSearchArray();
    }
}
