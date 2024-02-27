<?php

namespace App\Service\DungeonRoute;

use App\Models\Season;
use App\User;
use Illuminate\Support\Collection;

interface CoverageServiceInterface
{
    public function getForUser(User $user, Season $season): Collection;
}
