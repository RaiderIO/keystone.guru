<?php

namespace App\Http\Resources\CombatLog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Override;

/**
 * @OA\Schema(
 *      schema="CombatLogParseFailureEnvelope",
 *      type="object",
 *      required={"data"},
 *      @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CombatLogParseFailure"))
 *  )
 */
class CombatLogParseFailureEnvelopeResource extends ResourceCollection
{
    public $collects = CombatLogParseFailureResource::class;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return ['data' => $this->collection];
    }
}
