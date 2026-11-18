<?php

namespace App\Http\Resources\WalkGraph;

use App\Models\WalkGraph\WalkGraph;
use Illuminate\Http\Request;
use Override;

/**
 * The full graph: the summary plus the cell size and the compressed edge-count blob, base64-encoded.
 *
 * @property WalkGraph $resource
 */
class WalkGraphResource extends WalkGraphSummaryResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'cell_size' => $this->resource->cell_size,
            'edges'     => base64_encode($this->resource->edges),
        ]);
    }
}
