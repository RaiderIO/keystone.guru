<?php

namespace App\Repositories\Interfaces\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionCombatLogBatch                  create(array<string, mixed> $attributes)
 * @method LiveSessionCombatLogBatch|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionCombatLogBatch                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionCombatLogBatch                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                       save(LiveSessionCombatLogBatch $model)
 * @method bool                                       update(LiveSessionCombatLogBatch $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                       delete(LiveSessionCombatLogBatch $model)
 * @method Collection<int, LiveSessionCombatLogBatch> all()
 * @method bool                                       exists(array<int, string> $columns)
 */
interface LiveSessionCombatLogBatchRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Store a batch of gzip-compressed lines.
     *
     * @return bool false when the session already has a batch with this sequence number, which is then left as it was
     */
    public function storeBatch(LiveSession $liveSession, ?int $sequence, string $compressedLines): bool;

    /**
     * The highest sequence number ever accepted for the session, consumed batches included.
     */
    public function getHighestSequence(LiveSession $liveSession): ?int;

    /**
     * The highest sequence number among the session's consumed batches.
     */
    public function getHighestConsumedSequence(LiveSession $liveSession): ?int;

    /**
     * The batches not yet consumed, in the order their lines must be replayed: unsequenced batches first, then by
     * sequence, each by arrival.
     *
     * @return Collection<int, LiveSessionCombatLogBatch>
     */
    public function getPendingBatches(LiveSession $liveSession): Collection;

    /**
     * Mark exactly these batches consumed and release their lines; their sequence numbers stay reserved.
     *
     * @param Collection<int, int> $batchIds
     */
    public function markConsumed(Collection $batchIds): void;
}
