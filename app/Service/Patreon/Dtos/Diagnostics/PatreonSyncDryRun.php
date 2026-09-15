<?php

namespace App\Service\Patreon\Dtos\Diagnostics;

/**
 * The whole hourly sync, computed but not executed.
 */
class PatreonSyncDryRun
{
    /**
     * @param array<string, int>                   $resultCounts           Keyed by ApplyPaidBenefitsForMemberResult case name
     * @param array<int, PatreonMemberDiagnostics> $membersLosingBenefits  Members a real sync would revoke something from
     * @param array<int, PatreonMemberDiagnostics> $membersGainingBenefits Members a real sync would grant something to
     * @param array<int, PatreonMemberDiagnostics> $membersBlocked         Members skipped over unknown tiers or benefits
     */
    public function __construct(
        public readonly int   $pageCount,
        public readonly int   $memberCount,
        public readonly array $resultCounts,
        public readonly array $membersLosingBenefits,
        public readonly array $membersGainingBenefits,
        public readonly array $membersBlocked,
    ) {
    }
}
