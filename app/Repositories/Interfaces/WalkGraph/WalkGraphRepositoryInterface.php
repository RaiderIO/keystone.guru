<?php

namespace App\Repositories\Interfaces\WalkGraph;

use App\Models\WalkGraph\WalkGraph;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method WalkGraph                  create(array<string, mixed> $attributes)
 * @method WalkGraph|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method WalkGraph                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method WalkGraph                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                       save(WalkGraph $model)
 * @method bool                       update(WalkGraph $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                       delete(WalkGraph $model)
 * @method Collection<int, WalkGraph> all()
 * @method bool                       exists(array<string, mixed> $columns)
 */
interface WalkGraphRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUiMapId(int $uiMapId): ?WalkGraph;

    /**
     * @return Collection<int, int>
     */
    public function getUiMapIds(): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function saveForUiMapId(int $uiMapId, array $attributes): WalkGraph;
}
