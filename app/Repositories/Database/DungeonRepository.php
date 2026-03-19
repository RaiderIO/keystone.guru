<?php

namespace App\Repositories\Database;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use Illuminate\Support\Collection;

class DungeonRepository extends DatabaseRepository implements DungeonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Dungeon::class);
    }

    public function getAllMapIds(): Collection
    {
        return Dungeon::get('map_id')->pluck('map_id')->unique();
    }

    public function getByChallengeModeIdOrFail(int $challengeModeId): Dungeon
    {
        // Order by descending id so we get the most recent dungeon in case challenge modes overlap
        return Dungeon::where('challenge_mode_id', $challengeModeId)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    public function getMappingVersionByVersion(Dungeon $dungeon, int $version): ?MappingVersion
    {
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()->where('version', $version)->first();

        return $mappingVersion;
    }

    public function getByInstanceId(int $instanceId): ?Dungeon
    {
        return Dungeon::where('instance_id', $instanceId)->first();
    }
}
