<?php

namespace App\Service\Patreon\Dtos\Diagnostics;

use App\Models\Patreon\PatreonSyncRun;
use Illuminate\Support\Carbon;

/**
 * Everything known about one Keystone account's Patreon state, from both sides at once.
 */
class PatreonUserDiagnostics
{
    /**
     * @param array<int, string>            $storedBenefits      Benefit keys the account currently holds in the database
     * @param array<int, int>               $duplicateLinkIds    Other link rows pointing at this account, which make the email match ambiguous
     * @param PatreonMemberDiagnostics|null $member              The campaign member matched by the link's email, if the campaign lists one
     * @param string|null                   $emailDriftCandidate A masked campaign email that matches the *account's* email but not the link's - the fingerprint of a patron who changed their Patreon email after linking
     * @param PatreonSyncRun|null           $latestSyncRun       The newest recorded run, to compare `lastSeenAt` against
     */
    public function __construct(
        public readonly int                       $userId,
        public readonly string                    $username,
        public readonly ?int                      $patreonUserLinkId,
        public readonly ?string                   $maskedLinkEmail,
        public readonly ?string                   $maskedAccountEmail,
        public readonly bool                      $manuallyGranted,
        public readonly ?Carbon                   $lastSeenAt,
        public readonly ?string                   $lastSyncResult,
        public readonly array                     $storedBenefits,
        public readonly array                     $duplicateLinkIds,
        public readonly ?PatreonMemberDiagnostics $member,
        public readonly ?string                   $emailDriftCandidate,
        public readonly ?PatreonSyncRun           $latestSyncRun,
    ) {
    }

    /** Whether the newest recorded sync run finished after this link was last seen. */
    public function missedByLatestRun(): bool
    {
        if ($this->latestSyncRun === null || $this->patreonUserLinkId === null) {
            return false;
        }

        return $this->lastSeenAt === null || $this->lastSeenAt->lessThan($this->latestSyncRun->started_at);
    }
}
