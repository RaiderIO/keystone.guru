<?php

namespace App\Repositories;

use Eloquent;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(protected Eloquent|string $class)
    {
    }
}
