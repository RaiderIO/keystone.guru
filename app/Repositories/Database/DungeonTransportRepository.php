<?php

namespace App\Repositories\Database;

use App\Models\DungeonTransport;
use App\Repositories\Interfaces\DungeonTransportRepositoryInterface;

class DungeonTransportRepository extends DatabaseRepository implements DungeonTransportRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonTransport::class);
    }

    public function unlinkTransportsLinkedTo(int $dungeonTransportId): int
    {
        return DungeonTransport::query()
            ->where('linked_dungeon_transport_id', $dungeonTransportId)
            ->update(['linked_dungeon_transport_id' => null]);
    }
}
