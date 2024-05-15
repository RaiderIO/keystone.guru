<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    public function create(array $attributes): Model;

    public function insert(array $attributes): bool;

    public function find(int $id, string|array $columns = ['*']): ?Model;

    public function findOrFail(int $id, array|string $columns = ['*']): Model;

    public function findOrNew(int $id, array|string $columns = ['*']): Model;

    public function save(Model $model): bool;

    public function update(Model $model, array $attributes = [], array $options = []): bool;

    public function delete(Model $model): bool;

    public function all(): Collection;
}
