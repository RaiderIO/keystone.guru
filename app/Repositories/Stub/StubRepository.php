<?php

namespace App\Repositories\Stub;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class StubRepository extends BaseRepository
{
    private int $id = 1;

    public function create(array $attributes): Model
    {
        $attributes['id'] = $this->id++;

        return new $this->class($attributes);
    }

    public function insert(array $attributes): bool
    {
        return true;
    }

    public function find(int $id, string|array $columns = ['*']): Model
    {
        return new $this->class([
            'id' => $id,
        ]);
    }

    public function findOrFail(int $id, string|array $columns = ['*']): Model
    {
        return $this->find($id, $columns);
    }

    public function findOrNew(int $id, string|array $columns = ['*']): Model
    {
        return $this->find($id, $columns);
    }

    public function save(Model $model): bool
    {
        $model->id = $this->id++;

        return true;
    }

    public function update(Model $model, array $attributes = [], array $options = []): bool
    {
        foreach ($attributes as $key => $value) {
            $model->setAttribute($key, $value);
        }

        return true;
    }

    public function delete(Model $model): bool
    {
        return true;
    }

    public function all(): Collection
    {
        return collect();
    }
}
