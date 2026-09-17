<?php

namespace App\Policies;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DungeonRouteCollectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the collection.
     */
    public function view(?User $user, DungeonRouteCollection $dungeonRouteCollection): Response
    {
        if (!$dungeonRouteCollection->mayUserView($user)) {
            return $this->deny(__('policy.view_collection_not_published'));
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can update the collection.
     */
    public function edit(?User $user, DungeonRouteCollection $dungeonRouteCollection): bool
    {
        return $dungeonRouteCollection->mayUserEdit($user);
    }

    /**
     * Determine whether the user can delete the collection.
     */
    public function delete(?User $user, DungeonRouteCollection $dungeonRouteCollection): bool
    {
        return $dungeonRouteCollection->mayUserEdit($user);
    }
}
