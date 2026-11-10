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

    /** The in-game script of the portrait pipeline's `extract` step, unchanged apart from the placeholders. */
    private const string DISPLAY_PORTRAITS_LUA = <<<'LUA'
local displayIds = {
{ids_block}}

local resX = {res_x}
local resY = {res_y}
local portraitSizeX = 64
local portraitSizeY = 64
local maxTiles = 100

local tilesX = floor(resX / portraitSizeX)
local tilesY = floor(resY / portraitSizeY)
local numTilesNeeded = table.getn(displayIds)
local numTilesAvailable = min(maxTiles, tilesX * tilesY)
local numScreenshotsRequired = ceil(numTilesNeeded / numTilesAvailable)
local numFramesNeeded = min(numTilesNeeded, numTilesAvailable)

local frames = {}

for i=1,numFramesNeeded do
   local posX = (i - 1) % tilesX * portraitSizeX
   local posY = -floor((i - 1) / tilesX) * portraitSizeY

   local frame = CreateFrame("Frame", nil, UIParent)
   frame:SetSize(portraitSizeX, portraitSizeY)
   frame:SetScale(768 / resY / UIParent:GetScale())
   frame:SetPoint("TOPLEFT", UIParent, "TOPLEFT", posX, posY)

   local background = frame:CreateTexture(nil, "BACKGROUND")
   background:SetColorTexture(0, 0, 0)
   background:SetAllPoints(frame)
   background:SetDrawLayer("BACKGROUND", 1)

   local portrait = frame:CreateTexture(nil, "BACKGROUND")
   portrait:SetAllPoints(frame)
   portrait:SetDrawLayer("BACKGROUND", 2)

   frame.portrait = portrait

   table.insert(frames, frame)
end

local updateInterval = 2
local timeSum = updateInterval
local screenshotsDone = 0
local screenshotDone = true

local function onUpdate(self, elapsed)
   timeSum = timeSum + elapsed
   if timeSum < updateInterval then
      return
   end

   if not screenshotDone then
      Screenshot()
      screenshotDone = true
      screenshotsDone = screenshotsDone + 1
      if screenshotsDone >= numScreenshotsRequired then
         self:SetScript("OnUpdate", nil)
      end
      return
   end

   screenshotDone = false
   timeSum = 0

   local portraitsRemaining = numTilesNeeded - (screenshotsDone * numTilesAvailable)
   local portraitStartIndex = screenshotsDone * numTilesAvailable + 1
   local portraitEndIndex = portraitStartIndex + min(portraitsRemaining, numTilesAvailable) - 1

   for i=portraitStartIndex, portraitEndIndex do
      local displayId = displayIds[i]
      local displayFrame = frames[i - portraitStartIndex + 1]
      SetPortraitTextureFromCreatureDisplayID(displayFrame.portrait, displayId)
   end

   for i=portraitEndIndex-portraitStartIndex + 2,numFramesNeeded do
      frames[i]:Hide()
   end
end

local eventFrame = CreateFrame("Frame", nil, UIParent)
eventFrame:SetScript("OnUpdate", onUpdate)

LUA;

    public function __construct(
        private readonly OpenWorldNpcRepositoryInterface $openWorldNpcRepository,
        private readonly Filesystem                      $filesystem,
    ) {
    }

    public function exportBatch(
        int    $batchSize,
        int    $resolutionX,
        int    $resolutionY,
        string $outputDirectory,
        bool   $includeUnrenderable,
    ): OpenWorldNpcPortraitBatchExport {
        $npcs                  = $this->openWorldNpcRepository->getPortraitCandidates($batchSize, $includeUnrenderable);
        $candidateCount        = $this->openWorldNpcRepository->countPortraitCandidates($includeUnrenderable);
        $withoutDisplayIdCount = $this->openWorldNpcRepository->getWithoutPortrait()
            ->filter(static fn(OpenWorldNpc $npc) => $npc->display_id === null)
            ->count();

        $portraitsPerScreenshot = min(
            self::MAX_PORTRAITS_PER_SCREENSHOT,
            intdiv($resolutionX, self::PORTRAIT_SIZE) * intdiv($resolutionY, self::PORTRAIT_SIZE),
        );

        if ($npcs->isNotEmpty()) {
            $this->writeBatchFiles($npcs, $resolutionX, $resolutionY, $outputDirectory);
        }

        return new OpenWorldNpcPortraitBatchExport(
            $npcs,
            $outputDirectory,
            (int)ceil($npcs->count() / max(1, $portraitsPerScreenshot)),
            $candidateCount - $npcs->count(),
            $withoutDisplayIdCount,
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
    private function writeBatchFiles(Collection $npcs, int $resolutionX, int $resolutionY, string $outputDirectory): void
    {
        $this->filesystem->ensureDirectoryExists($outputDirectory);

        $this->filesystem->put(
            sprintf('%s/npcs.csv', $outputDirectory),
            $npcs->map(static fn(OpenWorldNpc $npc) => sprintf("\"%d\"\n", $npc->id))->prepend("\"id\"\n")->implode(''),
        );

        $this->filesystem->put(
            sprintf('%s/display_ids.csv', $outputDirectory),
            $npcs->map(static fn(OpenWorldNpc $npc) => sprintf("%d;%d\n", $npc->id, $npc->display_id))
                ->prepend("npc_id;display_id\n")
                ->implode(''),
        );

        $this->filesystem->put(
            sprintf('%s/display_portraits.lua', $outputDirectory),
            strtr(self::DISPLAY_PORTRAITS_LUA, [
                '{ids_block}' => $npcs->map(static fn(OpenWorldNpc $npc) => sprintf("%d,\n", $npc->display_id))->implode(''),
                '{res_x}'     => (string)$resolutionX,
                '{res_y}'     => (string)$resolutionY,
            ]),
        );
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
