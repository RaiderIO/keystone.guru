<?php

namespace App\Repositories\Database\Patreon;

use App\Models\Patreon\PatreonUserLink;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Patreon\PatreonUserLinkRepositoryInterface;

class PatreonUserLinkRepository extends DatabaseRepository implements PatreonUserLinkRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PatreonUserLink::class);
    }
}
