<?php

namespace App\Http\Resources\Patreon;

use App\Service\Patreon\Dtos\Diagnostics\PatreonMemberDiagnostics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PatreonMemberDiagnostics $resource
 */
class PatreonMemberDiagnosticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'member_id' => $this->resource->memberId,
            // Masked, never the real address - see App\Logic\Utils\EmailMasker
            'email'               => $this->resource->maskedEmail,
            'result'              => $this->resource->result,
            'linked'              => $this->resource->linked,
            'user_id'             => $this->resource->userId,
            'manually_granted'    => $this->resource->manuallyGranted,
            'entitled_tier_ids'   => $this->resource->entitledTierIds,
            'unresolved_tier_ids' => $this->resource->unresolvedTierIds,
            'unknown_benefits'    => $this->resource->unknownBenefits,
            'resolved_benefits'   => $this->resource->resolvedBenefits,
            'current_benefits'    => $this->resource->currentBenefits,
            'benefits_to_add'     => $this->resource->benefitsToAdd,
            'benefits_to_revoke'  => $this->resource->benefitsToRevoke,
            'patron_status'       => $this->resource->patronStatus,
            'last_charge_status'  => $this->resource->lastChargeStatus,
        ];
    }
}
