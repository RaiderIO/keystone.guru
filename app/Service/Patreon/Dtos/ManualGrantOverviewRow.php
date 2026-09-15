<?php

namespace App\Service\Patreon\Dtos;

use App\Models\Patreon\PatreonBenefit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * One row of the manual Patreon grants overview. Two different things end up looking like this: a
 * grant with an audit record, and a grant made before that record existed - which is only visible as
 * a Patreon link the admin panel fabricated, with no reason or granter attached to it.
 */
readonly class ManualGrantOverviewRow
{
    /**
     * @param Collection<int, PatreonBenefit> $benefits
     */
    public function __construct(
        public User       $user,
        public Carbon     $grantedAt,
        public Collection $benefits,
        public bool       $isLegacy,
        public ?string    $reason = null,
        public ?string    $grantedByName = null,
        public bool       $hasRealPatreonLink = false,
    ) {
    }
}
