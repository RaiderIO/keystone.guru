<?php

namespace App\Repositories\Database\Npc;

use App\Models\Npc\NpcBolsteringWhitelist;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcBolsteringWhitelistRepositoryInterface;

class NpcBolsteringWhitelistRepository extends DatabaseRepository implements NpcBolsteringWhitelistRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcBolsteringWhitelist::class);
    }
}
