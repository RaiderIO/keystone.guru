<?php

namespace App\Repositories\Database\Patreon;

use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Patreon\PatreonAdFreeGiveawayRepositoryInterface;

class PatreonAdFreeGiveawayRepository extends DatabaseRepository implements PatreonAdFreeGiveawayRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PatreonAdFreeGiveaway::class);
    }
}
