<?php

namespace App\Service\OpenWorldNpc\Dtos;

use App\Models\Npc\OpenWorldNpc;
use Illuminate\Support\Collection;

/**
 * One batch of open-world NPCs written out as input for the in-game portrait pipeline.
 */
class OpenWorldNpcPortraitBatchExport
{
    /**
     * @param Collection<int, OpenWorldNpc> $npcs                  The NPCs in the batch, in portrait order
     * @param int                           $screenshotCount       Screenshots the in-game script takes for this batch
     * @param int                           $remainingCount        Candidates left for later batches
     * @param int                           $withoutDisplayIdCount NPCs without a portrait that wait for a display id
     */
    public function __construct(
        private readonly Collection $npcs,
        private readonly string     $outputDirectory,
        private readonly int        $screenshotCount,
        private readonly int        $remainingCount,
        private readonly int        $withoutDisplayIdCount,
    ) {
    }

    /**
     * @return Collection<int, OpenWorldNpc>
     */
    public function getNpcs(): Collection
    {
        return $this->npcs;
    }

    public function getOutputDirectory(): string
    {
        return $this->outputDirectory;
    }

    public function getScreenshotCount(): int
    {
        return $this->screenshotCount;
    }

    public function getRemainingCount(): int
    {
        return $this->remainingCount;
    }

    public function getWithoutDisplayIdCount(): int
    {
        return $this->withoutDisplayIdCount;
    }
}
