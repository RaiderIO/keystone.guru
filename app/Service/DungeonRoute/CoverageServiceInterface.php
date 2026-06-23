<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

interface CoverageServiceInterface
{
    /**
     * @return Collection<int|string, EloquentCollection<int, DungeonRoute>>
     */
    public function getForUser(User $user, Season $season): Collection;
}
