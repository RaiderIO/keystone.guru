<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function create(array $attributes): Model;

    public function insert(array $attributes): bool;

    public function find(int $id): Model;

    public function save(Model $model): bool;

    public function delete(Model $model): bool;
}
