<?php

namespace App\Service\OpenWorldNpc\Dtos;

use Illuminate\Support\Collection;

/**
 * What one sync of Raider.IO's deadly-NPC list found and changed.
 */
class OpenWorldNpcSyncResult
{
    /**
     * @param Collection<int, int> $createdNpcIds          NPCs the file introduced
     * @param Collection<int, int> $renamedNpcIds          Known NPCs whose name in the file differed from ours
     * @param Collection<int, int> $skippedNpcIds          NPCs in the file that already exist in `npcs`
     * @param Collection<int, int> $displayIdFilledNpcIds  NPCs whose display id was found on Wowhead in this run
     * @param Collection<int, int> $displayIdMissingNpcIds NPCs whose display id is still unknown after this run
     * @param Collection<int, int> $withoutPortraitNpcIds  Every open-world NPC that has no portrait yet
     */
    public function __construct(
        private readonly int        $entryCount,
        private readonly int        $invalidEntryCount,
        private readonly Collection $createdNpcIds,
        private readonly Collection $renamedNpcIds,
        private readonly int        $unchangedCount,
        private readonly Collection $skippedNpcIds,
        private readonly Collection $displayIdFilledNpcIds,
        private readonly Collection $displayIdMissingNpcIds,
        private readonly Collection $withoutPortraitNpcIds,
    ) {
    }

    public function getEntryCount(): int
    {
        return $this->entryCount;
    }

    public function getInvalidEntryCount(): int
    {
        return $this->invalidEntryCount;
    }

    /**
     * @return Collection<int, int>
     */
    public function getCreatedNpcIds(): Collection
    {
        return $this->createdNpcIds;
    }

    /**
     * @return Collection<int, int>
     */
    public function getRenamedNpcIds(): Collection
    {
        return $this->renamedNpcIds;
    }

    public function getUnchangedCount(): int
    {
        return $this->unchangedCount;
    }

    /**
     * @return Collection<int, int>
     */
    public function getSkippedNpcIds(): Collection
    {
        return $this->skippedNpcIds;
    }

    /**
     * @return Collection<int, int>
     */
    public function getDisplayIdFilledNpcIds(): Collection
    {
        return $this->displayIdFilledNpcIds;
    }

    /**
     * @return Collection<int, int>
     */
    public function getDisplayIdMissingNpcIds(): Collection
    {
        return $this->displayIdMissingNpcIds;
    }

    /**
     * @return Collection<int, int>
     */
    public function getWithoutPortraitNpcIds(): Collection
    {
        return $this->withoutPortraitNpcIds;
    }
}
