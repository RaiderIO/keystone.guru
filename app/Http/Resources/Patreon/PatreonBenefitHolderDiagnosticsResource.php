<?php

namespace App\Http\Resources\Patreon;

use App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitHolderDiagnostics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PatreonBenefitHolderDiagnostics $resource
 */
class PatreonBenefitHolderDiagnosticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reason'                => $this->resource->reason->value,
            'user_id'               => $this->resource->userId,
            'username'              => $this->resource->username,
            'patreon_user_link_id'  => $this->resource->patreonUserLinkId,
            'masked_link_email'     => $this->resource->maskedLinkEmail,
            'masked_account_email'  => $this->resource->maskedAccountEmail,
            'stored_benefits'       => $this->resource->storedBenefits,
            'last_seen_at'          => $this->resource->lastSeenAt?->toIso8601String(),
            'last_sync_result'      => $this->resource->lastSyncResult,
            'duplicate_link_ids'    => $this->resource->duplicateLinkIds,
            'email_drift_candidate' => $this->resource->emailDriftCandidate,
        ];
    }
}
