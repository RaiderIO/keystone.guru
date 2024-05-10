<?php

namespace App\Repositories\Database\Metrics;

use App\Models\Metrics\Metric;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Metrics\MetricRepositoryInterface;

class MetricRepository extends DatabaseRepository implements MetricRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Metric::class);
    }
}
