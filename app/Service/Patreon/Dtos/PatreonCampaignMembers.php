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
     * @param array<int, array<string, mixed>> $members
     */
    public function __construct(
        public readonly array $members,
        public readonly int   $pageCount,
    ) {
    }
}
