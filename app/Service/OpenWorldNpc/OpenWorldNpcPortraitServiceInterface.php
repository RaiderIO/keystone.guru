<?php

namespace App\Service\OpenWorldNpc;

use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcPortraitBatchExport;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcPortraitBatchResult;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcPortraitReconcileResult;
use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcPortraitBatchException;
use App\Service\OpenWorldNpc\Exceptions\OpenWorldNpcPortraitsUnavailableException;
use Illuminate\Support\Collection;

interface OpenWorldNpcPortraitServiceInterface
{
    /**
     * Writes up to `$batchCount` batches of open-world NPCs without a portrait as input for the in-game portrait
     * pipeline, each in its own `batch_NN` directory under `$outputDirectory`: an `npcs.csv` in portrait order and an
     * `npc_portraits.lua` that renders the batch by NPC id, stamps `BATCH NN` on the screen and takes one screenshot.
     * Writes nothing when no NPC is waiting for a portrait.
     *
     * @param int $batchSize NPCs per batch; at most {@see getPortraitsPerScreenshot()}
     */
    public function exportBatches(
        int    $batchSize,
        int    $batchCount,
        int    $resolutionX,
        int    $resolutionY,
        string $outputDirectory,
        bool   $includeUnrenderable,
    ): OpenWorldNpcPortraitBatchExport;

    /**
     * How many portraits one screenshot at this resolution holds.
     */
    public function getPortraitsPerScreenshot(int $resolutionX, int $resolutionY): int;

    /**
     * Reads the NPC ids of an exported batch from its `npcs.csv`, in portrait order.
     *
     * @return Collection<int, int>
     *
     * @throws InvalidOpenWorldNpcPortraitBatchException
     */
    public function readBatchNpcIds(string $npcsCsvPath): Collection;

    /**
     * Marks a generated batch: an NPC whose portrait file holds a portrait gets `has_portrait`, an NPC whose file is
     * missing, blank or unreadable, or that is listed as unrenderable, is marked unrenderable and its file removed.
     *
     * @param Collection<int, int> $npcIds             The batch's NPC ids
     * @param Collection<int, int> $unrenderableNpcIds NPCs whose portrait came out wrong, regardless of their file
     *
     * @throws OpenWorldNpcPortraitsUnavailableException When the portrait directory is not where it is expected, or a
     *                                                   portrait file cannot be read or deleted; no state changes then
     */
    public function markBatch(Collection $npcIds, Collection $unrenderableNpcIds): OpenWorldNpcPortraitBatchResult;

    /**
     * Sets `has_portrait` of every open-world NPC to whether its portrait file exists.
     *
     * @throws OpenWorldNpcPortraitsUnavailableException When the portrait directory is not where it is expected
     */
    public function reconcile(): OpenWorldNpcPortraitReconcileResult;
}
