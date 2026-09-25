<?php

namespace Tests\Fixtures\Traits;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;

trait GrantsPatreonBenefits
{
    /**
     * Gives the user a Patreon benefit. The link and its benefits are dropped again by User::deleting.
     */
    protected function grantPatreonBenefit(User $user, string $benefit): void
    {
        $patreonUserLink = PatreonUserLink::factory()->manuallyGranted()->create(['user_id' => $user->id]);
        $user->update(['patreon_user_link_id' => $patreonUserLink->id]);

        PatreonUserBenefit::create([
            'patreon_user_link_id' => $patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[$benefit],
        ]);
    }
}
