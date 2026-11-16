<?php

namespace App\Service\OpenWorldNpc\Dtos;

use App\Models\Npc\OpenWorldNpc;
use Illuminate\Support\Collection;

/**
 * The batches of open-world NPCs written out as input for the in-game portrait pipeline, one in-game script and one
 * screenshot per batch.
 */
class OpenWorldNpcPortraitBatchExport
{
    /**
     * @param Collection<int, Collection<int, OpenWorldNpc>> $batches        The batches in script order, each in portrait order
     * @param int                                            $remainingCount Candidates left for a later export
     */
    public function __construct(
        private readonly Collection $batches,
        private readonly string     $outputDirectory,
        private readonly int        $remainingCount,
    ) {
    }

    /**
     * @return Collection<int, Collection<int, OpenWorldNpc>>
     */
    public function getBatches(): Collection
    {
        return $this->batches;
    }

    public function getNpcCount(): int
    {
        return $this->batches->sum(static fn(Collection $batch) => $batch->count());
    }

    public function getOutputDirectory(): string
    {
        return $this->outputDirectory;
    }

    public function getRemainingCount(): int
    {
        return $this->remainingCount;
    }
}
