<?php

namespace App\Http\Resources\WalkGraph;

use App\Models\WalkGraph\WalkGraph;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Override;

class WalkGraphSummaryEnvelopeResource extends ResourceCollection
{
    /**
     * @return array{data: array<int, WalkGraphSummaryResource>}
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                static fn(WalkGraph $walkGraph) => new WalkGraphSummaryResource($walkGraph),
            )->values()->all(),
        ];
    }
}
