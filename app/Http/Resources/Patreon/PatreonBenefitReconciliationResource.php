<?php

namespace App\Http\Resources\Patreon;

use App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitReconciliation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PatreonBenefitReconciliation $resource
 */
class PatreonBenefitReconciliationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                // The counts are true totals; the two lists below are capped
                'holder_count'    => $this->resource->holderCount,
                'unmatched_count' => $this->resource->unmatchedCount,
                'blocked_count'   => $this->resource->blockedCount,
                // No accompanying list on purpose - the next hourly sync revokes these by itself, and
                // `sync-dry-run`'s members_losing_benefits already names them one by one
                'downgraded_count'  => $this->resource->downgradedCount,
                'needs_attention'   => $this->resource->needsAttention(),
                'unmatched_holders' => PatreonBenefitHolderDiagnosticsResource::collection($this->resource->unmatchedHolders),
                'blocked_holders'   => PatreonBenefitHolderDiagnosticsResource::collection($this->resource->blockedHolders),
            ],
        ];
    }
}
