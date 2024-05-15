<?php

namespace App\Repositories\Database;

use App\Models\GameIcon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\GameIconRepositoryInterface;

class GameIconRepository extends DatabaseRepository implements GameIconRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(GameIcon::class);
    }
}
