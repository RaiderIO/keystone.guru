<?php

namespace App\Service\Patreon\Dtos;

use App\Models\Patreon\PatreonUserLink;

/**
 * Everything the hourly sync worked out about one campaign member, before it wrote anything.
 *
 * `PatreonService::applyPaidBenefitsForMember()` is implemented as "build one of these, then execute it",
 * so the diagnostic endpoints (#4373) can show exactly what a real sync would do to an account without
 * touching it. Computing the two on separate code paths would let the diagnosis drift away from the
 * behaviour it claims to describe.
 */
class PatreonMemberSyncPlan
{
    /**
     * @param string                          $memberId          The Patreon member id
     * @param string|null                     $memberEmail       The email Patreon reports for the member, if any
     * @param PatreonUserLink|null            $patreonUserLink   The link the member email matched, if any
     * @param array<int, string>              $entitledTierIds   Tier ids the member is currently entitled to
     * @param array<int, string>              $unresolvedTierIds Entitled tier ids the campaign response does not describe
     * @param array<int, string>              $resolvedBenefits  Benefit titles those tiers grant
     * @param array<int, string>              $unknownBenefits   Resolved titles that are missing from PatreonBenefit::ALL
     * @param array<int, string>              $currentBenefits   Benefit keys the account holds right now
     * @param array<int, string>              $benefitsToAdd     Benefits a sync would grant
     * @param array<int, string>              $benefitsToRevoke  Benefits a sync would take away
     * @param bool                            $manuallyGranted   Whether the link was granted through the admin pages
     * @param ApplyPaidBenefitsForMemberResult $result           What a real sync would return for this member
     */
    public function __construct(
        public readonly string                           $memberId,
        public readonly ?string                          $memberEmail,
        public readonly ?PatreonUserLink                 $patreonUserLink,
        public readonly array                            $entitledTierIds,
        public readonly array                            $unresolvedTierIds,
        public readonly array                            $resolvedBenefits,
        public readonly array                            $unknownBenefits,
        public readonly array                            $currentBenefits,
        public readonly array                            $benefitsToAdd,
        public readonly array                            $benefitsToRevoke,
        public readonly bool                             $manuallyGranted,
        public readonly ApplyPaidBenefitsForMemberResult $result,
    ) {
    }

    /**
     * Whether executing this plan would change anything at all - a sync that adds and revokes nothing is
     * the normal steady state for an already-correct account.
     */
    public function changesAnything(): bool
    {
        return $this->benefitsToAdd !== [] || $this->benefitsToRevoke !== [];
    }
}
