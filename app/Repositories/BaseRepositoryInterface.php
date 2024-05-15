<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    public function create(array $attributes): Model;

    public function insert(array $attributes): bool;

    public function find(int $id, array $columns = []): Model;

    public function findOrFail(int $id, array $columns = []): Model;

    public function findOrNew(int $id, array $columns = []): Model;

    public function save(Model $model): bool;

    public function update(Model $model, array $attributes = [], array $options = []): bool;

    public function delete(Model $model): bool;

    public function all(): Collection;
}
