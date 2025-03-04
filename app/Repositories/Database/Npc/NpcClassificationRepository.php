<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcClassification;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcClassificationRepositoryInterface;

class NpcClassificationRepository extends DatabaseRepository implements NpcClassificationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcClassification::class);
    }
}
