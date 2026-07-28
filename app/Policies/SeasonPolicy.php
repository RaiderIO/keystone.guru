<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SeasonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the season.
     *
     * $season is resolved by query rather than route-bound, so it may be null (an unknown season
     * index) - callers authorize this with `Gate::authorize('view', [Season::class, $season])` to
     * let the policy resolve even without an instance. A missing or retired season is gone for
     * everyone, not withheld from this particular user - deny with a 404 rather than the generic
     * 403 so the response reflects that.
     */
    public function view(?User $user, ?Season $season): Response
    {
        if ($season === null || !$season->expansion->active) {
            return $this->denyAsNotFound(__('policy.season_not_active'));
        }

        return $this->allow();
    }
}
