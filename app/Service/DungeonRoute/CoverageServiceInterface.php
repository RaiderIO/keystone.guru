<?php


namespace App\Service\DungeonRoute;

use App\User;
use Illuminate\Support\Collection;

interface CoverageServiceInterface
{
    /**
     * @param User $user
     * @return Collection
     */
    function getForUser(User $user): Collection;
}
