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
     * Writes the next batch of open-world NPCs without a portrait as input for the in-game portrait pipeline: the
     * `npcs.csv`, `display_ids.csv` and `display_portraits.lua` its `extract` step would write. Writes nothing when
     * no NPC is waiting for a portrait.
     */
    public function exportBatch(
        int    $batchSize,
        int    $resolutionX,
        int    $resolutionY,
        string $outputDirectory,
        bool   $includeUnrenderable,
    ): OpenWorldNpcPortraitBatchExport;

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
     * @throws OpenWorldNpcPortraitsUnavailableException When the portrait directory is not where it is expected
     */
    public function markBatch(Collection $npcIds, Collection $unrenderableNpcIds): OpenWorldNpcPortraitBatchResult;

    /**
     * Sets `has_portrait` of every open-world NPC to whether its portrait file exists.
     *
     * @throws OpenWorldNpcPortraitsUnavailableException When the portrait directory is not where it is expected
     */
    public function reconcile(): OpenWorldNpcPortraitReconcileResult;
}
