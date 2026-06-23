<?php

namespace App\Repositories\Interfaces;

use App\Models\Dungeon;
use App\Models\Season;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Season                  create(array<string, mixed> $attributes)
 * @method Season|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Season                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Season                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                    save(Season $model)
 * @method bool                    update(Season $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                    delete(Season $model)
 * @method Collection<int, Season> all()
 * @method bool                    exists(array<int, string> $columns)
 */
interface SeasonRepositoryInterface extends BaseRepositoryInterface
{
    public function getMostRecentSeasonForDungeon(Dungeon $dungeon): ?Season;

    public function getUpcomingSeasonForDungeon(Dungeon $dungeon): ?Season;
}
