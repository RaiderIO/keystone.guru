<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use Random\RandomException;

class DungeonRouteRepository extends DatabaseRepository implements DungeonRouteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRoute::class);
    }

    public function generateRandomPublicKey(): string
    {
        try {
            return DungeonRoute::generateRandomPublicKey();
        } catch (RandomException $e) {
            return 'RandomException!';
        }
    }
}
