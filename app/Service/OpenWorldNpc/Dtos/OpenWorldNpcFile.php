<?php

namespace App\Service\OpenWorldNpc\Dtos;

use Illuminate\Support\Collection;

/**
 * The entries of Raider.IO's deadly-NPC list that could be read, and how many could not.
 */
class OpenWorldNpcFile
{
    /**
     * @param Collection<int, OpenWorldNpcFileEntry> $entries Keyed by NPC id
     */
    public function __construct(
        private readonly Collection $entries,
        private readonly int        $invalidEntryCount,
    ) {
    }

    /**
     * @return Collection<int, OpenWorldNpcFileEntry> Keyed by NPC id
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function getInvalidEntryCount(): int
    {
        return $this->invalidEntryCount;
    }
}
