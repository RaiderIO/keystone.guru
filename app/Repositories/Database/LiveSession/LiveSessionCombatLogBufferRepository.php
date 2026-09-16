<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionCombatLogBufferRepositoryInterface;

class LiveSessionCombatLogBufferRepository extends DatabaseRepository implements LiveSessionCombatLogBufferRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionCombatLogBuffer::class);
    }
}
