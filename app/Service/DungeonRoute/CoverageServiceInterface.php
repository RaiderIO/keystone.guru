<?php

namespace App\Service\DungeonRoute;

use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Collection;

interface CoverageServiceInterface
{
    /**

     * @return Collection<int, mixed>
     */

    public function getForUser(User $user, Season $season): Collection;
}
