<?php

namespace App\Service\DungeonRoute;

use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Collection;

interface CoverageServiceInterface
{
    public function getForUser(User $user, Season $season): Collection;
}
