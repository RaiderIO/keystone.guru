<?php

namespace App\Service\Patreon\Dtos;

/**
 * The campaign's members, plus how many pages it took to fetch them.
 *
 * The page count is carried alongside the members so `patreon:refreshmembers` can record it on the
 * `patreon_sync_runs` row (#4373) - a run that fetched fewer members or fewer pages than the one before
 * it is the signature of a truncated fetch, and that comparison is the only way to see it from outside
 * production.
 */
class PatreonCampaignMembers
{
    /**
     * `$members` is deliberately empty whenever `$truncated` is true: a partial member list must never
     * reach the code that applies benefits, because every member the fetch never saw is indistinguishable
     * there from a member who cancelled. The counts still describe how far the fetch got, which is the
     * part worth recording on the run row.
     *
     * @param array<int, array<string, mixed>> $members
     * @param int                              $pageCount How many pages were requested, the failed one included
     * @param int                              $rowCount  How many members arrived before the fetch stopped
     * @param bool                             $truncated Whether the fetch stopped before the last page
     */
    public function __construct(
        public readonly array $members,
        public readonly int   $pageCount,
        public readonly int   $rowCount,
        public readonly bool  $truncated,
    ) {
    }
}
