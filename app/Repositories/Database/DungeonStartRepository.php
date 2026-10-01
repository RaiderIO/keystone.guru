<?php

namespace App\Repositories\Database;

use App\Models\DungeonStart;
use App\Repositories\Interfaces\DungeonStartRepositoryInterface;

class DungeonStartRepository extends DatabaseRepository implements DungeonStartRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonStart::class);
    }

    public function isDungeonStartOfMappingVersion(int $id, int $mappingVersionId): bool
    {
        return DungeonStart::query()
            ->whereKey($id)
            ->where('mapping_version_id', $mappingVersionId)
            ->exists();
    }

    public function findMatchingDungeonStartIdInMappingVersion(int $dungeonStartId, int $mappingVersionId): ?int
    {
        $comment = DungeonStart::query()->whereKey($dungeonStartId)->value('comment');
        if (empty($comment)) {
            return null;
        }

        $matchingId = DungeonStart::query()
            ->where('mapping_version_id', $mappingVersionId)
            ->where('comment', $comment)
            ->value('id');

        return $matchingId !== null ? (int)$matchingId : null;
    }
}
