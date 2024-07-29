<?php

namespace App\Repositories\Database;

use App\Models\Brushline;
use App\Repositories\Interfaces\BrushlineRepositoryInterface;

class BrushlineRepository extends DatabaseRepository implements BrushlineRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Brushline::class);
    }
}
