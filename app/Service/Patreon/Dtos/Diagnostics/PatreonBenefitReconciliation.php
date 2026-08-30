<?php

namespace App\Service\Patreon\Dtos\Diagnostics;

/**
 * Every account holding more Patreon benefits than the campaign currently grants (#4386).
 *
 * The hourly sync only ever revokes benefits from members the campaign returns *and* can resolve, so
 * this cross-references the other direction: the benefit rows in the database against the campaign, to
 * find the accounts no sync will ever correct.
 *
 * The counts are true totals; the two lists are capped for display. `downgradedCount` has no list on
 * purpose - a member mid-downgrade appears in it until the next hourly run corrects them, so listing
 * those would mean this report never reads clean and stops working as a "is anything wrong" signal.
 * Their per-member detail already exists as {@see PatreonSyncDryRun::$membersLosingBenefits}.
 */
class PatreonBenefitReconciliation
{
    /**
     * @param int                                         $holderCount      Non-excluded links holding at least one benefit row - the population the three counts below are drawn from
     * @param array<int, PatreonBenefitHolderDiagnostics> $unmatchedHolders Accounts the campaign no longer matches at all; nothing will ever revoke these
     * @param array<int, PatreonBenefitHolderDiagnostics> $blockedHolders   Accounts a sync refuses to touch over unknown tiers or benefits
     */
    public function __construct(
        public readonly int   $holderCount,
        public readonly int   $unmatchedCount,
        public readonly int   $blockedCount,
        public readonly int   $downgradedCount,
        public readonly array $unmatchedHolders,
        public readonly array $blockedHolders,
    ) {
    }

    /**
     * Whether anything in this report needs a human. Downgrades are excluded on purpose - the next
     * hourly sync handles those without help.
     */
    public function needsAttention(): bool
    {
        return $this->unmatchedCount > 0 || $this->blockedCount > 0;
    }
}
