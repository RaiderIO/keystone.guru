<?php

namespace App\Service\Patreon\Dtos\Diagnostics;

use App\Service\Patreon\Dtos\PatreonOverEntitlementReason;
use Illuminate\Support\Carbon;

/**
 * One account holding Patreon benefits the campaign does not justify, reduced to what is safe to hand
 * back over the API (#4386).
 *
 * Deliberately shaped like {@see PatreonUserDiagnostics} rather than {@see PatreonMemberDiagnostics}:
 * the accounts that matter here are precisely the ones with no usable campaign member behind them, so
 * the database side is all there is to report.
 */
class PatreonBenefitHolderDiagnostics
{
    /**
     * @param array<int, string> $storedBenefits      Benefit keys the account currently holds in the database
     * @param array<int, int>    $duplicateLinkIds    Other link rows pointing at this account, which make the sync's email match ambiguous
     * @param string|null        $emailDriftCandidate The masked campaign email that matches the account but not the link, when the reason is EmailDrift
     */
    public function __construct(
        public readonly PatreonOverEntitlementReason $reason,
        public readonly ?int                         $userId,
        public readonly ?string                      $username,
        public readonly int                          $patreonUserLinkId,
        public readonly ?string                      $maskedLinkEmail,
        public readonly ?string                      $maskedAccountEmail,
        public readonly array                        $storedBenefits,
        public readonly ?Carbon                      $lastSeenAt,
        public readonly ?string                      $lastSyncResult,
        public readonly array                        $duplicateLinkIds,
        public readonly ?string                      $emailDriftCandidate,
    ) {
    }
}
