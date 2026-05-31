<?php

namespace App\Repositories\Database\Feature;

use App\Models\Feature\Feature;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Feature\FeatureRepositoryInterface;

class FeatureRepository extends DatabaseRepository implements FeatureRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Feature::class);
    }
}
