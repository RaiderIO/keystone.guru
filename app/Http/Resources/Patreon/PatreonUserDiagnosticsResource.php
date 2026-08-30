<?php

namespace App\Http\Resources\Patreon;

use App\Service\Patreon\Dtos\Diagnostics\PatreonUserDiagnostics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PatreonUserDiagnostics $resource
 */
class PatreonUserDiagnosticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => [
                'user_id'  => $this->resource->userId,
                'username' => $this->resource->username,

                // The database side: what the account holds right now, and when a sync last touched it
                'patreon_user_link_id' => $this->resource->patreonUserLinkId,
                'link_email'           => $this->resource->maskedLinkEmail,
                'account_email'        => $this->resource->maskedAccountEmail,
                'manually_granted'     => $this->resource->manuallyGranted,
                'stored_benefits'      => $this->resource->storedBenefits,
                'last_seen_at'         => $this->resource->lastSeenAt?->toIso8601String(),
                'last_sync_result'     => $this->resource->lastSyncResult,
                'duplicate_link_ids'   => $this->resource->duplicateLinkIds,

                // The campaign side, absent when Patreon could not be reached
                'member' => $this->resource->member === null
                    ? null
                    : new PatreonMemberDiagnosticsResource($this->resource->member),

                // A campaign member carrying this account's email while the link carries another one:
                // the patron changed their Patreon email after linking and has been unmatched since
                'email_drift_candidate' => $this->resource->emailDriftCandidate,

                // True when the newest recorded run started after this link was last seen - the sync ran
                // but never reached this patron, rather than the campaign having dropped them
                'missed_by_latest_run' => $this->resource->missedByLatestRun(),
                'latest_sync_run'      => $this->resource->latestSyncRun === null
                    ? null
                    : new PatreonSyncRunResource($this->resource->latestSyncRun),
            ],
        ];
    }
}
