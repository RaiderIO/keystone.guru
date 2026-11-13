<?php

namespace App\Service\OpenWorldNpc;

use App\Models\GameVersion\GameVersion;
use App\Repositories\Interfaces\GameVersion\GameVersionRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\Npc\OpenWorldNpcRepositoryInterface;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcFile;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcFileEntry;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcSyncResult;
use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcFileException;
use App\Service\OpenWorldNpc\Logging\OpenWorldNpcSyncServiceLoggingInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OpenWorldNpcSyncService implements OpenWorldNpcSyncServiceInterface
{
    /** Raider.IO's death data is retail's open world, so its NPCs are looked up on retail Wowhead. */
    private const string WOWHEAD_GAME_VERSION = GameVersion::GAME_VERSION_RETAIL;

    public function __construct(
        private readonly OpenWorldNpcFileReaderInterface         $fileReader,
        private readonly OpenWorldNpcRepositoryInterface         $openWorldNpcRepository,
        private readonly NpcRepositoryInterface                  $npcRepository,
        private readonly GameVersionRepositoryInterface          $gameVersionRepository,
        private readonly WowheadServiceInterface                 $wowheadService,
        private readonly OpenWorldNpcSyncServiceLoggingInterface $log,
    ) {
    }

    public function isConfigured(): bool
    {
        $bucket = config(sprintf('filesystems.disks.%s.bucket', $this->getDiskName()));

        return is_string($bucket) && $bucket !== '';
    }

    public function sync(): OpenWorldNpcSyncResult
    {
        $diskName = $this->getDiskName();
        $filePath = (string)config('keystoneguru.raider_io.open_world_npcs.file_path');

        $this->log->syncStart($diskName, $filePath);

        try {
            $file = $this->readFile($diskName, $filePath);

            $entries       = $file->getEntries();
            $skippedNpcIds = $this->npcRepository->getExistingIds($entries->keys());
            $entriesToSync = $entries->except($skippedNpcIds->all());
            $knownNpcs     = $this->openWorldNpcRepository->findAllByIdKeyed($entriesToSync->keys());

            $createdNpcIds  = collect();
            $renamedNpcIds  = collect();
            $unchangedCount = 0;
            /** @var OpenWorldNpcFileEntry $entry */
            foreach ($entriesToSync as $npcId => $entry) {
                $knownNpc = $knownNpcs->get($npcId);

                if ($knownNpc === null) {
                    $this->openWorldNpcRepository->create([
                        'id'   => $npcId,
                        'name' => $entry->getName(),
                    ]);
                    $createdNpcIds->push($npcId);
                } elseif ($knownNpc->name !== $entry->getName()) {
                    $this->log->syncNpcRenamed($npcId, $knownNpc->name, $entry->getName());
                    $this->openWorldNpcRepository->update($knownNpc, ['name' => $entry->getName()]);
                    $renamedNpcIds->push($npcId);
                } else {
                    $unchangedCount++;
                }
            }

            [$displayIdFilledNpcIds, $displayIdMissingNpcIds] = $this->fillDisplayIds();

            $withoutPortraitNpcIds = $this->openWorldNpcRepository->getWithoutPortrait()->pluck('id');

            $this->log->syncResult(
                $createdNpcIds->count(),
                $renamedNpcIds->count(),
                $unchangedCount,
                $skippedNpcIds->count(),
                $displayIdFilledNpcIds->count(),
                $withoutPortraitNpcIds->count(),
            );

            return new OpenWorldNpcSyncResult(
                $entries->count(),
                $file->getInvalidEntryCount(),
                $createdNpcIds,
                $renamedNpcIds,
                $unchangedCount,
                $skippedNpcIds->sort()->values(),
                $displayIdFilledNpcIds,
                $displayIdMissingNpcIds,
                $withoutPortraitNpcIds,
            );
        } finally {
            $this->log->syncEnd();
        }
    }

    /**
     * @throws InvalidOpenWorldNpcFileException
     */
    private function readFile(string $diskName, string $filePath): OpenWorldNpcFile
    {
        $disk = Storage::disk($diskName);

        if (!$disk->exists($filePath)) {
            $this->log->syncFileNotFound();

            throw new InvalidOpenWorldNpcFileException(sprintf('No file at %s on disk %s', $filePath, $diskName));
        }

        try {
            $file = $this->fileReader->read((string)$disk->get($filePath));
        } catch (InvalidOpenWorldNpcFileException $exception) {
            $this->log->syncInvalidFile($exception->getMessage());

            throw $exception;
        }

        if ($file->getEntries()->isEmpty()) {
            $this->log->syncEmptyFile();
        } elseif ($file->getInvalidEntryCount() > 0) {
            $this->log->syncInvalidEntries($file->getInvalidEntryCount(), $file->getEntries()->count() + $file->getInvalidEntryCount());
        }

        return $file;
    }

    /**
     * Looks up the display id of the open-world NPCs that have none yet, including those of earlier runs that Wowhead
     * could not answer for, up to the configured number of lookups per run.
     *
     * @return array{0: Collection<int, int>, 1: Collection<int, int>} The NPC ids that got a display id, and every NPC id still without one
     */
    private function fillDisplayIds(): array
    {
        $filled = collect();

        $lookupsPerRun        = max(0, (int)config('keystoneguru.raider_io.open_world_npcs.display_id_lookups_per_run'));
        $npcsWithoutDisplayId = $this->openWorldNpcRepository->getWithoutDisplayId($lookupsPerRun);
        $gameVersion          = $npcsWithoutDisplayId->isEmpty() ? null : $this->gameVersionRepository->findByKey(self::WOWHEAD_GAME_VERSION);
        if ($gameVersion === null) {
            return [$filled, $this->openWorldNpcRepository->getWithoutDisplayId()->pluck('id')];
        }

        foreach ($npcsWithoutDisplayId->values() as $index => $openWorldNpc) {
            if ($index > 0) {
                $this->wowheadService->sleep();
            }

            try {
                $displayId = $this->wowheadService->getNpcDisplayIdByNpcId($gameVersion, $openWorldNpc->id, $openWorldNpc->name);
            } catch (Throwable $throwable) {
                $this->log->fillDisplayIdsException($openWorldNpc->id, $throwable);
                $displayId = null;
            }

            if ($displayId === null) {
                $this->log->fillDisplayIdsNotFound($openWorldNpc->id);
            } else {
                $this->openWorldNpcRepository->update($openWorldNpc, ['display_id' => $displayId]);
                $filled->push($openWorldNpc->id);
            }
        }

        return [$filled, $this->openWorldNpcRepository->getWithoutDisplayId()->pluck('id')];
    }

    private function getDiskName(): string
    {
        return (string)config('keystoneguru.raider_io.open_world_npcs.disk');
    }
}
