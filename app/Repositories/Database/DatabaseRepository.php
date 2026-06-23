<?php

namespace App\Repositories\Database;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class DatabaseRepository extends BaseRepository
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Model
    {
        return $this->class::create($attributes);
    }

    /**
     * @param array<string, mixed>|array<int, array<string, mixed>> $attributes
     */
    public function insert(array $attributes): bool
    {
        return $this->class::insert($attributes);
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function find(int $id, array|string $columns = ['*']): ?Model
    {
        return $this->class::find($id, $columns);
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function findOrFail(int $id, array|string $columns = ['*']): Model
    {
        return $this->class::findOrFail($id, $columns);
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function findOrNew(int $id, array|string $columns = ['*']): Model
    {
        return $this->class::findOrNew($id, $columns);
    }

    public function save(Model $model): bool
    {
        return $model->save();
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $options
     */
    public function update(Model $model, array $attributes = [], array $options = []): bool
    {
        return $model->update($attributes, $options);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * @param array<string, mixed> $columns
     */
    public function exists(array $columns): bool
    {
        return $this->class::where($columns)->exists();
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->class::all();
    }
}
