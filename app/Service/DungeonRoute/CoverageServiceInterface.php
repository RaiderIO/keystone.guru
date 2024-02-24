<?php


namespace App\Service\DungeonRoute;

use App\Models\Season;
use App\User;
use Illuminate\Support\Collection;

interface CoverageServiceInterface
{
    /**
     * @return Collection
     */
    function getForUser(User $user, Season $season): Collection;
}
