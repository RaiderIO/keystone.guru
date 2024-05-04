<?php

namespace App\Repositories;

use Eloquent;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Eloquent|string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }
}
