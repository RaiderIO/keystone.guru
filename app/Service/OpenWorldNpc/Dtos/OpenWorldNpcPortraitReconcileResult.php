<?php

namespace App\Service\OpenWorldNpc\Dtos;

use App\Models\Npc\OpenWorldNpc;
use Illuminate\Support\Collection;

/**
 * What reconciling the open-world NPCs' portrait state with the portrait files changed.
 */
class OpenWorldNpcPortraitReconcileResult
{
    /**
     * @param Collection<int, int>          $gainedNpcIds     NPCs whose portrait file exists but were not marked as having one
     * @param Collection<int, int>          $lostNpcIds       NPCs marked as having a portrait whose file does not exist
     * @param Collection<int, OpenWorldNpc> $unrenderableNpcs NPCs the in-game pipeline could not render
     */
    public function __construct(
        private readonly int        $withPortraitCount,
        private readonly int        $withoutPortraitCount,
        private readonly Collection $gainedNpcIds,
        private readonly Collection $lostNpcIds,
        private readonly Collection $unrenderableNpcs,
    ) {
    }

    public function getWithPortraitCount(): int
    {
        return $this->withPortraitCount;
    }

    public function getWithoutPortraitCount(): int
    {
        return $this->withoutPortraitCount;
    }

    /**
     * @return Collection<int, int>
     */
    public function getGainedNpcIds(): Collection
    {
        return $this->gainedNpcIds;
    }

    /**
     * @return Collection<int, int>
     */
    public function getLostNpcIds(): Collection
    {
        return $this->lostNpcIds;
    }

    /**
     * @return Collection<int, OpenWorldNpc>
     */
    public function getUnrenderableNpcs(): Collection
    {
        return $this->unrenderableNpcs;
    }
}
