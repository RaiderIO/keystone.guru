<?php

namespace App\Repositories\Database;

use App\Models\PublishedState;
use App\Repositories\Interfaces\PublishedStateRepositoryInterface;

class PublishedStateRepository extends DatabaseRepository implements PublishedStateRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PublishedState::class);
    }
}
