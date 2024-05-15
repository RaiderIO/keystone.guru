<?php

namespace App\Repositories\Database\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\GameVersion\GameVersionRepositoryInterface;

class GameVersionRepository extends DatabaseRepository implements GameVersionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(GameVersion::class);
    }
}
