<?php

namespace App\Repositories\Database\WalkGraph;

use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\WalkGraph\EnemyPackWalkDistanceRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EnemyPackWalkDistanceRepository extends DatabaseRepository implements EnemyPackWalkDistanceRepositoryInterface
{
    private const int INSERT_CHUNK_SIZE = 1000;

    public function __construct()
    {
        parent::__construct(EnemyPackWalkDistance::class);
    }

    public function getByMappingVersionId(int $mappingVersionId): Collection
    {
        return EnemyPackWalkDistance::query()
            ->where('mapping_version_id', $mappingVersionId)
            ->get()
            ->toBase();
    }

    public function replaceForFloor(int $mappingVersionId, int $floorId, array $rows): void
    {
        $now = Carbon::now();

        DB::transaction(static function () use ($mappingVersionId, $floorId, $rows, $now) {
            EnemyPackWalkDistance::query()
                ->where('mapping_version_id', $mappingVersionId)
                ->where('floor_id', $floorId)
                ->toBase()
                ->delete();

            foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
                EnemyPackWalkDistance::query()->insert(array_map(static fn(array $row) => array_merge($row, [
                    'mapping_version_id' => $mappingVersionId,
                    'floor_id'           => $floorId,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]), $chunk));
            }
        });
    }
}
