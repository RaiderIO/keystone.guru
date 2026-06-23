<?php

namespace App\Repositories\Stub;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class StubRepository extends BaseRepository
{
    private int $id = 1;
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Model
    {
        $attributes['id'] = $this->id++;

        return new $this->class($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): bool
    {
        return true;
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function find(int $id, string|array $columns = ['*']): Model
    {
        return new $this->class([
            'id' => $id,
        ]);
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function findOrFail(int $id, string|array $columns = ['*']): Model
    {
        return $this->find($id, $columns);
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function findOrNew(int $id, string|array $columns = ['*']): Model
    {
        return $this->find($id, $columns);
    }

    public function save(Model $model): bool
    {
        $model->setAttribute($model->getKeyName(), $this->id++);

        return true;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $options
     */
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

    /**
     * @param array<string, mixed> $columns
     */
    public function exists(array $columns): bool
    {
        return true;
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return collect();
    }
}
