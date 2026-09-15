<?php

namespace App\Service\Patreon\Dtos;

/**
 * The campaign's members, plus how many pages it took to fetch them.
 */
class PatreonCampaignMembers
{
    /**
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
