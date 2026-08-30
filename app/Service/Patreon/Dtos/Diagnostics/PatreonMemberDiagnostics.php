<?php

namespace App\Service\Patreon\Dtos\Diagnostics;

use App\Logic\Utils\EmailMasker;
use App\Service\Patreon\Dtos\PatreonMemberSyncPlan;

/**
 * One member's sync outcome, reduced to what is safe to hand back over the API.
 *
 * Built from a {@see PatreonMemberSyncPlan} rather than recomputed, so it describes what a real sync
 * would do and not merely what a second implementation thinks it would do.
 */
class PatreonMemberDiagnostics
{
    /**
     * @param array<int, string> $entitledTierIds
     * @param array<int, string> $unresolvedTierIds
     * @param array<int, string> $unknownBenefits
     * @param array<int, string> $resolvedBenefits
     * @param array<int, string> $currentBenefits
     * @param array<int, string> $benefitsToAdd
     * @param array<int, string> $benefitsToRevoke
     */
    public function __construct(
        public readonly string  $memberId,
        public readonly ?string $maskedEmail,
        public readonly string  $result,
        public readonly bool    $linked,
        public readonly ?int    $userId,
        public readonly bool    $manuallyGranted,
        public readonly array   $entitledTierIds,
        public readonly array   $unresolvedTierIds,
        public readonly array   $unknownBenefits,
        public readonly array   $resolvedBenefits,
        public readonly array   $currentBenefits,
        public readonly array   $benefitsToAdd,
        public readonly array   $benefitsToRevoke,
        public readonly ?string $patronStatus,
        public readonly ?string $lastChargeStatus,
    ) {
    }

    /**
     * @param array<string, mixed> $member The raw campaign member the plan was built from
     */
    public static function fromPlan(PatreonMemberSyncPlan $plan, array $member): self
    {
        $patronStatus     = $member['attributes']['patron_status'] ?? null;
        $lastChargeStatus = $member['attributes']['last_charge_status'] ?? null;

        return new self(
            memberId: $plan->memberId,
            maskedEmail: EmailMasker::mask($plan->memberEmail),
            result: $plan->result->name,
            linked: $plan->patreonUserLink !== null,
            userId: $plan->patreonUserLink?->user_id,
            manuallyGranted: $plan->manuallyGranted,
            entitledTierIds: $plan->entitledTierIds,
            unresolvedTierIds: $plan->unresolvedTierIds,
            unknownBenefits: $plan->unknownBenefits,
            resolvedBenefits: $plan->resolvedBenefits,
            currentBenefits: $plan->currentBenefits,
            benefitsToAdd: $plan->benefitsToAdd,
            benefitsToRevoke: $plan->benefitsToRevoke,
            patronStatus: is_string($patronStatus) ? $patronStatus : null,
            lastChargeStatus: is_string($lastChargeStatus) ? $lastChargeStatus : null,
        );
    }
}
