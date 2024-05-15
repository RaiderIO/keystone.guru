<?php

namespace App\Repositories\Database;

use App\Models\LiveSession;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSessionRepositoryInterface;

class LiveSessionRepository extends DatabaseRepository implements LiveSessionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSession::class);
    }
}
