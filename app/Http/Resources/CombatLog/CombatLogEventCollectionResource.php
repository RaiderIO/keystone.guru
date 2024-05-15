<?php

namespace App\Http\Resources\CombatLog;

use App\Models\CombatLog\CombatLogEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class CombatLogEventCollectionResource
 *
 * @author Wouter
 *
 * @since 02/05/2024
 */
class CombatLogEventCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                static fn(CombatLogEvent $combatLogEvent) => new CombatLogEventResource($combatLogEvent)
            )->toArray(),
        ];
    }
}
