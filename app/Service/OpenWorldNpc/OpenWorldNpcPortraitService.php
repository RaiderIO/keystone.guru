<?php

namespace App\Service\OpenWorldNpc;

use App\Models\Npc\OpenWorldNpc;
use App\Repositories\Interfaces\Npc\OpenWorldNpcRepositoryInterface;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcPortraitBatchExport;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcPortraitBatchResult;
use App\Service\OpenWorldNpc\Dtos\OpenWorldNpcPortraitReconcileResult;
use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcPortraitBatchException;
use App\Service\OpenWorldNpc\Exceptions\OpenWorldNpcPortraitsUnavailableException;
use Illuminate\Contracts\Filesystem\Filesystem as Disk;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class OpenWorldNpcPortraitService implements OpenWorldNpcPortraitServiceInterface
{
    /** Mirrors the portrait pipeline's `portraits.py`: its frames are 64x64 and one screenshot holds at most 100. */
    private const int PORTRAIT_SIZE                = 64;
    private const int MAX_PORTRAITS_PER_SCREENSHOT = 100;

    /** Present in every portrait directory; its absence means the directory is not mounted where we look. */
    private const string UNKNOWN_PORTRAIT_FILE = 'unknown.png';

    /** Highest per-channel spread a tile may have and still count as blank: the pipeline's black frame, compressed. */
    private const int BLANK_TILE_CHANNEL_SPREAD = 16;

    /** The in-game script that renders one batch by NPC id and takes its screenshot, with placeholders, relative to resources/. */
    public const string NPC_PORTRAITS_LUA_TEMPLATE = 'assets/lua/npc_portraits.lua';

    public function __construct(
        private readonly OpenWorldNpcRepositoryInterface $openWorldNpcRepository,
        private readonly Filesystem                      $filesystem,
    ) {
    }

    public function exportBatches(
        int    $batchSize,
        int    $batchCount,
        int    $resolutionX,
        int    $resolutionY,
        string $outputDirectory,
        bool   $includeUnrenderable,
    ): OpenWorldNpcPortraitBatchExport {
        $candidates = $this->openWorldNpcRepository->getWithoutPortrait()
            ->reject(static fn(OpenWorldNpc $npc) => !$includeUnrenderable && $npc->portrait_unrenderable)
            ->values();

        $batches = $candidates->take($batchSize * $batchCount)->chunk($batchSize)->map(static fn(Collection $batch) => $batch->values());

        foreach ($batches->values() as $index => $batch) {
            $this->writeBatchFiles($batch, $index + 1, $batches->count(), $resolutionX, $resolutionY, $outputDirectory);
        }

        return new OpenWorldNpcPortraitBatchExport(
            $batches->values(),
            $outputDirectory,
            $candidates->count() - $batches->sum(static fn(Collection $batch) => $batch->count()),
        );
    }

    public function getPortraitsPerScreenshot(int $resolutionX, int $resolutionY): int
    {
        return min(
            self::MAX_PORTRAITS_PER_SCREENSHOT,
            intdiv($resolutionX, self::PORTRAIT_SIZE) * intdiv($resolutionY, self::PORTRAIT_SIZE),
        );
    }

    public function readBatchNpcIds(string $npcsCsvPath): Collection
    {
        if (!$this->filesystem->isFile($npcsCsvPath)) {
            throw new InvalidOpenWorldNpcPortraitBatchException(sprintf('%s does not exist', $npcsCsvPath));
        }

        $lines = collect(preg_split('/\R/', trim($this->filesystem->get($npcsCsvPath))) ?: []);
        if (trim((string)$lines->shift(), '"') !== 'id') {
            throw new InvalidOpenWorldNpcPortraitBatchException(sprintf('%s does not start with an "id" header', $npcsCsvPath));
        }

        return $lines->map(static function (string $line) use ($npcsCsvPath): int {
            $npcId = trim(trim($line), '"');
            if (!ctype_digit($npcId)) {
                throw new InvalidOpenWorldNpcPortraitBatchException(sprintf('%s holds an invalid NPC id "%s"', $npcsCsvPath, $line));
            }

            return (int)$npcId;
        })->values();
    }

    public function markBatch(Collection $npcIds, Collection $unrenderableNpcIds): OpenWorldNpcPortraitBatchResult
    {
        $disk = $this->getPortraitsDisk();

        $allNpcIds  = $npcIds->merge($unrenderableNpcIds)->unique()->values();
        $knownNpcs  = $this->openWorldNpcRepository->findAllByIdKeyed($allNpcIds);
        $unknownIds = $allNpcIds->reject(static fn(int $npcId) => $knownNpcs->has($npcId))->values();

        $portraitNpcIds  = collect();
        $unrenderableIds = collect();
        $removedNpcIds   = collect();
        foreach ($knownNpcs->keys() as $npcId) {
            $fileName = $this->getPortraitFileName($npcId);
            if (!$disk->exists($fileName)) {
                $unrenderableIds->push($npcId);

                continue;
            }

            if (!$unrenderableNpcIds->contains($npcId)) {
                $imageData = $disk->get($fileName);
                if ($imageData === null) {
                    throw new OpenWorldNpcPortraitsUnavailableException(sprintf('Unable to read %s; nothing was changed', $fileName));
                }

                if (!$this->isBlank($imageData)) {
                    $portraitNpcIds->push($npcId);

                    continue;
                }
            }

            $unrenderableIds->push($npcId);
            $removedNpcIds->push($npcId);
        }

        $undeletableFileNames = $removedNpcIds
            ->map(fn(int $npcId) => $this->getPortraitFileName($npcId))
            ->reject(static fn(string $fileName) => $disk->delete($fileName));
        if ($undeletableFileNames->isNotEmpty()) {
            throw new OpenWorldNpcPortraitsUnavailableException(sprintf(
                'Unable to delete %s; no portrait state was changed',
                $undeletableFileNames->implode(', '),
            ));
        }

        $this->openWorldNpcRepository->updatePortraitState($portraitNpcIds, true, false);
        $this->openWorldNpcRepository->updatePortraitState($unrenderableIds, false, true);

        return new OpenWorldNpcPortraitBatchResult(
            $portraitNpcIds->sort()->values(),
            $unrenderableIds->sort()->values(),
            $removedNpcIds->sort()->values(),
            $unknownIds->sort()->values(),
        );
    }

    public function reconcile(): OpenWorldNpcPortraitReconcileResult
    {
        $disk = $this->getPortraitsDisk();

        $gainedNpcIds         = collect();
        $lostNpcIds           = collect();
        $withPortraitCount    = 0;
        $withoutPortraitCount = 0;
        /** @var OpenWorldNpc $npc */
        foreach ($this->openWorldNpcRepository->all() as $npc) {
            $exists = $disk->exists($this->getPortraitFileName($npc->id));
            $exists ? $withPortraitCount++ : $withoutPortraitCount++;

            if ($exists && !$npc->has_portrait) {
                $gainedNpcIds->push($npc->id);
            } elseif (!$exists && $npc->has_portrait) {
                $lostNpcIds->push($npc->id);
            }
        }

        $this->openWorldNpcRepository->updatePortraitState($gainedNpcIds, true, false);
        $this->openWorldNpcRepository->updatePortraitState($lostNpcIds, false, false);

        return new OpenWorldNpcPortraitReconcileResult(
            $withPortraitCount,
            $withoutPortraitCount,
            $gainedNpcIds->sort()->values(),
            $lostNpcIds->sort()->values(),
            $this->openWorldNpcRepository->getUnrenderablePortraits(),
        );
    }

    /**
     * @param Collection<int, OpenWorldNpc> $npcs
     */
    private function writeBatchFiles(
        Collection $npcs,
        int        $batchNumber,
        int        $batchCount,
        int        $resolutionX,
        int        $resolutionY,
        string     $outputDirectory,
    ): void {
        $batchLabel     = sprintf('%02d', $batchNumber);
        $batchDirectory = sprintf('%s/%s', $outputDirectory, $this->getBatchDirectoryName($batchNumber));
        $this->filesystem->ensureDirectoryExists($batchDirectory);

        $this->filesystem->put(
            sprintf('%s/npcs.csv', $batchDirectory),
            $npcs->map(static fn(OpenWorldNpc $npc) => sprintf("\"%d\"\n", $npc->id))->prepend("\"id\"\n")->implode(''),
        );

        $this->filesystem->put(
            sprintf('%s/npc_portraits.lua', $batchDirectory),
            strtr($this->filesystem->get(resource_path(self::NPC_PORTRAITS_LUA_TEMPLATE)), [
                '{ids_block}'   => $npcs->map(static fn(OpenWorldNpc $npc) => sprintf("%d,\n", $npc->id))->implode(''),
                '{batch}'       => $batchLabel,
                '{batch_count}' => sprintf('%02d', $batchCount),
                '{res_x}'       => (string)$resolutionX,
                '{res_y}'       => (string)$resolutionY,
            ]),
        );
    }

    private function getBatchDirectoryName(int $batchNumber): string
    {
        return sprintf('batch_%02d', $batchNumber);
    }

    /**
     * Whether a cropped tile shows nothing but one colour, which is what the pipeline's black frame looks like when
     * the client drew no portrait into it. An unreadable image counts as blank too.
     */
    private function isBlank(string $imageData): bool
    {
        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            return true;
        }

        $min = [255, 255, 255];
        $max = [0, 0, 0];
        for ($x = 0, $width = imagesx($image); $x < $width; $x++) {
            for ($y = 0, $height = imagesy($image); $y < $height; $y++) {
                $colour = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                foreach ([$colour['red'], $colour['green'], $colour['blue']] as $channel => $value) {
                    $min[$channel] = min($min[$channel], $value);
                    $max[$channel] = max($max[$channel], $value);
                }
            }
        }

        foreach ($min as $channel => $value) {
            if ($max[$channel] - $value > self::BLANK_TILE_CHANNEL_SPREAD) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws OpenWorldNpcPortraitsUnavailableException
     */
    private function getPortraitsDisk(): Disk
    {
        $diskName = (string)config('keystoneguru.raider_io.open_world_npcs.portraits_disk');
        $disk     = Storage::disk($diskName);

        if (!$disk->exists(self::UNKNOWN_PORTRAIT_FILE)) {
            throw new OpenWorldNpcPortraitsUnavailableException(sprintf(
                'The %s disk holds no %s; is the assets repository checked out and mounted where the disk expects it?',
                $diskName,
                self::UNKNOWN_PORTRAIT_FILE,
            ));
        }

        return $disk;
    }

    private function getPortraitFileName(int $npcId): string
    {
        return sprintf('%d.png', $npcId);
    }
}
