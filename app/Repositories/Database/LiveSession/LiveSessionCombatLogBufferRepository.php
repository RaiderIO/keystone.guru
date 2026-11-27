<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionCombatLogBufferRepositoryInterface;

class LiveSessionCombatLogBufferRepository extends DatabaseRepository implements LiveSessionCombatLogBufferRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionCombatLogBuffer::class);
    }

    public function findByLiveSession(LiveSession $liveSession): ?LiveSessionCombatLogBuffer
    {
        return LiveSessionCombatLogBuffer::query()
            ->where('live_session_id', $liveSession->id)
            ->first();
    }

    public function saveCheckpoint(LiveSession $liveSession, string $compressedBuffer): void
    {
        LiveSessionCombatLogBuffer::query()->upsert(
            [['live_session_id' => $liveSession->id, 'buffer' => $compressedBuffer]],
            ['live_session_id'],
            ['buffer'],
        );
    }
}
