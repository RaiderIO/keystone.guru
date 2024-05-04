<?php

namespace App\Repositories\Stub;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

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

    public function find(int $id): Model
    {
        return new $this->class([
            'id' => $id,
        ]);
    }

    public function save(Model $model): bool
    {
        $model->id = $this->id++;

        return true;
    }

    public function delete(Model $model): bool
    {
        return true;
    }
}
