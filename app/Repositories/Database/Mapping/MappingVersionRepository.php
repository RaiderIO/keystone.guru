<?php

namespace App\Repositories\Database\Mapping;

use App\Models\Mapping\MappingVersion;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Mapping\MappingVersionRepositoryInterface;
use Illuminate\Support\Collection;

class MappingVersionRepository extends DatabaseRepository implements MappingVersionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(MappingVersion::class);
    }

    /**
     * @return Collection<int, MappingVersion>
     */
    public function getAllOrderedByDungeonId(): Collection
    {
        return MappingVersion::orderBy('dungeon_id')->get();
    }

    /**
     * @return Collection<int, MappingVersion>
     */
    public function getNewestPerGameVersion(int $dungeonId): Collection
    {
        return MappingVersion::query()
            ->where('dungeon_id', $dungeonId)
            ->orderByDesc('version')
            ->get()
            ->unique('game_version_id')
            ->values()
            ->toBase();
    }
}
