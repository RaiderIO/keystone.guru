<?php

namespace App\Repositories\Database;

use App\Models\NpcClassification;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\NpcClassificationRepositoryInterface;

class NpcClassificationRepository extends DatabaseRepository implements NpcClassificationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcClassification::class);
    }
}
