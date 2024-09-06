<?php

namespace App\Repositories\Database\Timewalking;

use App\Models\Timewalking\TimewalkingEvent;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Timewalking\TimewalkingEventRepositoryInterface;

class TimewalkingEventRepository extends DatabaseRepository implements TimewalkingEventRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(TimewalkingEvent::class);
    }
}
