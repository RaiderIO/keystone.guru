<?php

namespace App\Service\OpenWorldNpc;

use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcSyncResult;
use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcFileException;

interface OpenWorldNpcSyncServiceInterface
{
    /**
     * Whether the disk the deadly-NPC list is read from has a bucket configured.
     */
    public function isConfigured(): bool;

    /**
     * Reads Raider.IO's deadly-NPC list and adds or renames the open-world NPCs in it, then fills in the display ids
     * that are still unknown. Never deletes: an NPC missing from the file keeps its row.
     *
     * @throws InvalidOpenWorldNpcFileException When the file is missing or cannot be read; nothing is written then
     */
    public function sync(): OpenWorldNpcSyncResult;
}
