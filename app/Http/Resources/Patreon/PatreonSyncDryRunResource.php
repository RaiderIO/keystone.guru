<?php

namespace App\Http\Resources\Patreon;

use App\Service\Patreon\Dtos\Diagnostics\PatreonSyncDryRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PatreonSyncDryRun $resource
 */
class PatreonSyncDryRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'pages_fetched'            => $this->resource->pageCount,
                'members_fetched'          => $this->resource->memberCount,
                'result_counts'            => $this->resource->resultCounts,
                'members_losing_benefits'  => PatreonMemberDiagnosticsResource::collection($this->resource->membersLosingBenefits),
                'members_gaining_benefits' => PatreonMemberDiagnosticsResource::collection($this->resource->membersGainingBenefits),
                'members_blocked'          => PatreonMemberDiagnosticsResource::collection($this->resource->membersBlocked),
            ],
        ];
    }
}
