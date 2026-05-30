<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcCharacteristic;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcCharacteristicRepositoryInterface;

class NpcCharacteristicRepository extends DatabaseRepository implements NpcCharacteristicRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcCharacteristic::class);
    }
}
