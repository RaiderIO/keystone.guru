<?php

namespace App\Service\OpenWorldNpc\Dtos;

use Illuminate\Support\Collection;

/**
 * What marking one generated portrait batch found and changed.
 */
class OpenWorldNpcPortraitBatchResult
{
    /**
     * @param Collection<int, int> $portraitNpcIds     NPCs now marked as having a portrait
     * @param Collection<int, int> $unrenderableNpcIds NPCs now marked as unrenderable
     * @param Collection<int, int> $removedNpcIds      NPCs whose blank or unreadable portrait file was removed
     * @param Collection<int, int> $unknownNpcIds      Ids in the batch that are not open-world NPCs
     */
    public function __construct(
        private readonly Collection $portraitNpcIds,
        private readonly Collection $unrenderableNpcIds,
        private readonly Collection $removedNpcIds,
        private readonly Collection $unknownNpcIds,
    ) {
    }

    /**
     * @return Collection<int, int>
     */
    public function getPortraitNpcIds(): Collection
    {
        return $this->portraitNpcIds;
    }

    /**
     * @return Collection<int, int>
     */
    public function getUnrenderableNpcIds(): Collection
    {
        return $this->unrenderableNpcIds;
    }

    /**
     * @return Collection<int, int>
     */
    public function getRemovedNpcIds(): Collection
    {
        return $this->removedNpcIds;
    }

    /**
     * @return Collection<int, int>
     */
    public function getUnknownNpcIds(): Collection
    {
        return $this->unknownNpcIds;
    }
}
