<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Model;

    /**
     * @param array<string, mixed>|array<int, array<string, mixed>> $attributes
     */
    public function insert(array $attributes): bool;

    /**
     * @param array<int, string>|string $columns
     */
    public function find(int $id, string|array $columns = ['*']): ?Model;

    /**
     * @param array<int, string>|string $columns
     */
    public function findOrFail(int $id, array|string $columns = ['*']): Model;

    /**
     * @param array<int, string>|string $columns
     */
    public function findOrNew(int $id, array|string $columns = ['*']): Model;

    public function save(Model $model): bool;

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $options
     */
    public function update(Model $model, array $attributes = [], array $options = []): bool;

    public function delete(Model $model): bool;

    /**
     * @param array<string, mixed> $columns
     */
    public function exists(array $columns): bool;

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection;
}
