<?php

namespace App\Http\Resources\Patreon;

use App\Models\Patreon\PatreonSyncRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PatreonSyncRun $resource
 */
class PatreonSyncRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->resource->id,
            'started_at'               => $this->resource->started_at->toIso8601String(),
            'finished_at'              => $this->resource->finished_at?->toIso8601String(),
            'pages_fetched'            => $this->resource->pages_fetched,
            'members_fetched'          => $this->resource->members_fetched,
            'truncated'                => $this->resource->truncated,
            'members_applied'          => $this->resource->members_applied,
            'members_not_linked'       => $this->resource->members_not_linked,
            'members_unknown_benefits' => $this->resource->members_unknown_benefits,
            'members_unknown_tiers'    => $this->resource->members_unknown_tiers,
            'members_failed'           => $this->resource->members_failed,
            'successful'               => $this->resource->successful,
            'failure_reason'           => $this->resource->failure_reason,
        ];
    }
}
