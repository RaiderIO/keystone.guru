<?php

namespace App\Repositories\Database;

use App\Models\DungeonStart;
use App\Repositories\Interfaces\DungeonStartRepositoryInterface;
use Illuminate\Support\Collection;

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

    /**
     * @return Collection<int, array{id: int, text: string}>
     */
    public function getDungeonStartsForMappingVersion(int $mappingVersionId): Collection
    {
        return DungeonStart::query()
            ->where('mapping_version_id', $mappingVersionId)
            ->orderBy('id')
            ->get(['id', 'comment'])
            ->values()
            ->map(static fn(DungeonStart $dungeonStart, int $index) => [
                'id'   => $dungeonStart->id,
                'text' => $dungeonStart->getDisplayText($index),
            ]);
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
