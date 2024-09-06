<?php

namespace App\Repositories\Database\Metrics;

use App\Models\Metrics\MetricAggregation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Metrics\MetricAggregationRepositoryInterface;

class MetricAggregationRepository extends DatabaseRepository implements MetricAggregationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MetricAggregation::class);
    }
}
