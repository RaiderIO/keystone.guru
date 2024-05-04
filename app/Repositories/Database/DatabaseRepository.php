<?php

namespace App\Repositories\Database;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

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

    public function find(int $id): Model
    {
        return $this->class::find($id);
    }

    public function save(Model $model): bool
    {
        return $model->save();
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }
}
