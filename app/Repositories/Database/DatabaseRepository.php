<?php

namespace App\Repositories\Database;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class DatabaseRepository extends BaseRepository
{
    public function create(array $attributes): Model
    {
        return $this->class::create($attributes);
    }

    public function insert(array $attributes): bool
    {
        return $this->class::insert($attributes);
    }

    public function find(int $id, array|string $columns = ['*']): ?Model
    {
        return $this->class::find($id, $columns);
    }

    public function findOrFail(int $id, array|string $columns = ['*']): Model
    {
        return $this->class::findOrFail($id, $columns);
    }

    public function findOrNew(int $id, array|string $columns = ['*']): Model
    {
        return $this->class::findOrNew($id, $columns);
    }

    public function save(Model $model): bool
    {
        return $model->save();
    }

    public function update(Model $model, array $attributes = [], array $options = []): bool
    {
        return $model->update($attributes, $options);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function all(): Collection
    {
        return $this->class::all();
    }
}
