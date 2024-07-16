<?php

namespace App\Repositories\Database;

use App\Models\Npc\NpcBolsteringWhitelist;
use App\Repositories\Interfaces\NpcBolsteringWhitelistRepositoryInterface;

class NpcBolsteringWhitelistRepository extends DatabaseRepository implements NpcBolsteringWhitelistRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NpcBolsteringWhitelist::class);
    }
}
