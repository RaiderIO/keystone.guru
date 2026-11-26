<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionCombatLogBatchRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LiveSessionCombatLogBatchRepository extends DatabaseRepository implements LiveSessionCombatLogBatchRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionCombatLogBatch::class);
    }

    public function storeBatch(LiveSession $liveSession, ?int $sequence, string $compressedLines): bool
    {
        try {
            LiveSessionCombatLogBatch::query()->insert([
                'live_session_id' => $liveSession->id,
                'sequence'        => $sequence,
                'lines'           => $compressedLines,
                'created_at'      => Carbon::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    public function getHighestSequence(LiveSession $liveSession): ?int
    {
        $highestSequence = LiveSessionCombatLogBatch::query()
            ->where('live_session_id', $liveSession->id)
            ->max('sequence');

        return $highestSequence === null ? null : (int)$highestSequence;
    }

    public function getPendingBatches(LiveSession $liveSession): Collection
    {
        return LiveSessionCombatLogBatch::query()
            ->where('live_session_id', $liveSession->id)
            ->whereNull('consumed_at')
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();
    }

    public function markConsumed(Collection $batchIds): void
    {
        if ($batchIds->isEmpty()) {
            return;
        }

        LiveSessionCombatLogBatch::query()
            ->whereIn('id', $batchIds)
            ->update([
                'lines'       => null,
                'consumed_at' => Carbon::now(),
            ]);
    }
}
