<?php

namespace App\Repositories\Database;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DungeonRepository extends DatabaseRepository implements DungeonRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Dungeon::class);
    }
    /**
     * @return Collection<int, mixed>
     */
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

    public function getMappingVersionByVersion(Dungeon $dungeon, GameVersion $gameVersion, int $version): ?MappingVersion
    {
        // `version` is only unique per game_version_id (see #3720/#3754) - scoped explicitly by
        // $gameVersion rather than left ambiguous.
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()
            ->where('game_version_id', $gameVersion->id)
            ->where('version', $version)
            ->first();

        return $mappingVersion;
    }

    public function getByInstanceId(int $instanceId): ?Dungeon
    {
        return Dungeon::where('instance_id', $instanceId)->first();
    }

    public function getByMappingVersion(int $challengeModeId, GameVersion $gameVersion, ?int $mappingVersion): ?Dungeon
    {
        if ($mappingVersion === null) {
            return null;
        }

        // Order by descending id so we get the most recent dungeon in case challenge modes overlap.
        // Both conditions must match the SAME mapping version row - two separate whereRelation() calls
        // would each be their own EXISTS clause and could each be satisfied by a different row.
        return Dungeon::where('challenge_mode_id', $challengeModeId)
            ->orderByDesc('id')
            ->whereHas('mappingVersions', function (Builder $query) use ($mappingVersion, $gameVersion): void {
                $query->where('version', $mappingVersion)
                    ->where('game_version_id', $gameVersion->id);
            })
            ->first();
    }
}
