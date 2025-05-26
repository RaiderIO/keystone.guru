<?php

namespace App\Service\DungeonRoute;

use App\Jobs\ProcessRouteFloorThumbnail;
use App\Jobs\ProcessRouteFloorThumbnailCustom;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Models\File;
use App\Models\Floor\Floor;
use App\Service\DungeonRoute\Logging\ThumbnailServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Storage;
use Symfony\Component\Process\Process;
use Throwable;

class ThumbnailService implements ThumbnailServiceInterface
{
    public const THUMBNAIL_FOLDER_PATH = '/thumbnails';

    public const THUMBNAIL_CUSTOM_FOLDER_PATH = '/thumbnails_custom';

    public function __construct(
        private ThumbnailServiceLoggingInterface $log
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts = 0): ?DungeonRouteThumbnail
    {
        try {
            $this->log->createThumbnailStart($dungeonRoute->public_key, $floorIndex, $attempts);

            return $this->doCreateThumbnail(
                $dungeonRoute,
                $floorIndex,
                self::THUMBNAIL_FOLDER_PATH,
            );
        } finally {
            $this->log->createThumbnailEnd();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createThumbnailCustom(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?int         $zoomLevel = null,
        ?int         $quality = null): ?DungeonRouteThumbnail
    {
        try {
            $this->log->createThumbnailCustomStart($dungeonRoute->public_key, $floorIndex, $attempts, $viewportWidth, $viewportHeight, $imageWidth, $imageHeight, $zoomLevel, $quality);

            return $this->doCreateThumbnail(
                $dungeonRoute,
                $floorIndex,
                self::THUMBNAIL_CUSTOM_FOLDER_PATH,
                $viewportWidth,
                $viewportHeight,
                $imageWidth,
                $imageHeight,
                $zoomLevel,
                $quality ?? config('keystoneguru.api.dungeon_route.thumbnail.default_quality')
            );
        } finally {
            $this->log->createThumbnailCustomEnd();
        }
    }

    private function doCreateThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        string       $targetFolder,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?int         $zoomLevel = null,
        ?int         $quality = null
    ): ?DungeonRouteThumbnail {
        $result = null;

        try {
            $this->log->doCreateThumbnailStart(
                $dungeonRoute->public_key,
                $floorIndex,
                $targetFolder,
                $viewportWidth,
                $viewportHeight,
                $imageWidth,
                $imageHeight,
                $zoomLevel,
                $quality
            );
            if (app()->isDownForMaintenance()) {
                $this->log->doCreateThumbnailMaintenanceMode();

                return null;
            }

            $viewportWidth  ??= config('keystoneguru.api.dungeon_route.thumbnail.default_viewport_width');
            $viewportHeight ??= config('keystoneguru.api.dungeon_route.thumbnail.default_viewport_height');
            $imageWidth     ??= config('keystoneguru.api.dungeon_route.thumbnail.default_image_width');
            $imageHeight    ??= config('keystoneguru.api.dungeon_route.thumbnail.default_image_height');
            $zoomLevel      ??= config('keystoneguru.api.dungeon_route.thumbnail.default_zoom_level');

            // 1. Headless chrome saves file in a temp location
            // 2. File is downsized to a smaller thumbnail (can't make the browser window smaller since that'd mess up the image)
            // 3. Moved to public folder

            $filename = self::getFilename($dungeonRoute, $floorIndex);

            $tmpFile            = sprintf('/tmp/%s', $filename);
            $tmpFileAfterResize = sprintf('/tmp/resized_%s', $filename);

            // puppeteer chromium-browser
            $process = new Process([
                'node',
                // Script to execute
                resource_path('assets/puppeteer/route_thumbnail.js'),
                // First argument; where to navigate
                route('dungeonroute.preview', [
                    'dungeon'      => $dungeonRoute->dungeon,
                    'dungeonroute' => $dungeonRoute->public_key,
                    'title'        => $dungeonRoute->getTitleSlug(),
                    'floorIndex'   => $floorIndex,
                    'secret'       => config('keystoneguru.thumbnail.preview_secret'),
                    'z'            => $zoomLevel,
                ]),
                // Second argument; where to save the resulting image
                $tmpFile,
                $viewportWidth,
                $viewportHeight,
            ]);

            $this->log->doCreateThumbnailProcessStart($process->getCommandLine());

            $process->run();

            if ($process->isSuccessful()) {
                if (!file_exists($tmpFile)) {
                    $this->log->doCreateThumbnailFileNotFoundDidPuppeteerDownloadChromium($tmpFile);
                } else {
                    try {
                        // We've updated the thumbnail; make sure the route is updated, so it doesn't get updated anymore
                        $dungeonRoute->thumbnail_updated_at = Carbon::now()->toDateTimeString();
                        // Do not update the timestamps of the route! Otherwise, we'll just keep on updating the timestamp
                        $dungeonRoute->timestamps = false;
                        $dungeonRoute->save();

                        // Ensure our write path exists
                        if (!is_dir($targetFolder)) {
                            mkdir($targetFolder, 0755, true);
                        }

                        // Rescale it
                        $this->log->doCreateThumbnailRescale($tmpFile, $tmpFileAfterResize);
                        (new ImageManager(new ImagickDriver()))
                            ->read($tmpFile)
                            ->resize($imageWidth, $imageHeight)
                            ->save($tmpFileAfterResize, $quality);

                        $target = self::getTargetFilePath($dungeonRoute, $floorIndex, $targetFolder);

                        // Remove any old .png file that may be there
                        $oldPngFilePath = str_replace('.jpg', '.png', $target);
                        if (file_exists($oldPngFilePath) && unlink($oldPngFilePath)) {
                            $this->log->doCreateThumbnailRemovedOldPngFile();
                        }

                        // Image now exists in target location; compress it and move it to the target location
                        // Log::channel('scheduler')->info('Compressing image..');
                        // $this->compressPng($tmpScaledFile, $target);

                        $result = $this->attachThumbnailToDungeonRoute(
                            $dungeonRoute,
                            $floorIndex,
                            $target,
                            file_get_contents($tmpFileAfterResize),
                            $targetFolder === self::THUMBNAIL_CUSTOM_FOLDER_PATH,
                        );
                    } catch (Throwable $e) {
                        $this->log->doCreateThumbnailException($e);
                    } finally {
                        // Cleanup
                        $removedTmpFile = $removedTmpFileAfterResize = null;
                        if (file_exists($tmpFile)) {
                            $removedTmpFile = unlink($tmpFile);
                        }
                        if (file_exists($tmpFileAfterResize)) {
                            $removedTmpFileAfterResize = unlink($tmpFileAfterResize);
                        }

                        if ($removedTmpFile || $removedTmpFileAfterResize) {
                            $this->log->doCreateThumbnailRemovedTmpFileSuccess();
                        } else if ($removedTmpFile === false || $removedTmpFileAfterResize === false) {
                            $this->log->doCreateThumbnailRemovedTmpFileFailure();
                        }
                    }
                }
            }

            // Log any errors that may have occurred
            $errors = $process->getErrorOutput();
            if (!empty($errors)) {
                $this->log->doCreateThumbnailError($errors);

                return null;
            }
        } finally {
            $this->log->doCreateThumbnailEnd();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function queueThumbnailRefresh(DungeonRoute $dungeonRoute, bool $force = false): bool
    {
        $result = false;

        if ($dungeonRoute->thumbnail_refresh_queued_at
            ->addHours(config('keystoneguru.thumbnail.refresh_requeue_hours'))
            ->isPast()
        ) {
            if ($dungeonRoute->mappingVersion === null) {
                $this->log->queueThumbnailRefreshMappingVersionNull($dungeonRoute->public_key);
                // Do not return - we just assume the thumbnail is generated. Otherwise this will keep spamming
                // the logs with this error when it really isn't that important.
            } else {
                foreach ($dungeonRoute->dungeon->floorsForMapFacade($dungeonRoute->mappingVersion, true)->active()->get() as $floor) {
                    /** @var Floor $floor */
                    // Set it for processing in a queue
                    ProcessRouteFloorThumbnail::dispatch($dungeonRoute, $floor->index, $force);
                    $result = true;
                }
            }

            // Temporarily disable timestamps since we don't want this action to update the updated_at
            $dungeonRoute->timestamps                  = false;
            $dungeonRoute->thumbnail_refresh_queued_at = Carbon::now()->toDateTimeString();
            $dungeonRoute->save();

            // Re-enable them
            $dungeonRoute->timestamps = true;
        } else {
            $this->log->queueThumbnailRefreshAlreadyQueued($dungeonRoute->public_key);
        }

        return $result;
    }

    /**
     * @param Collection<DungeonRoute> $dungeonRoutes
     * @param bool                     $force
     * @return bool
     */
    public function queueThumbnailRefreshIfMissing(Collection $dungeonRoutes, bool $force = false): bool
    {
        $result = false;

        foreach ($dungeonRoutes as $dungeonRoute) {
            if ($dungeonRoute->has_thumbnail) {
                continue;
            }

            /** @var DungeonRoute $dungeonRoute */
            if ($this->queueThumbnailRefresh($dungeonRoute, $force)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function queueThumbnailRefreshForApi(
        DungeonRoute $dungeonRoute,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?int         $zoomLevel = null,
        ?int         $quality = null): Collection
    {
        $result = collect();

        // Generate thumbnails for _all_ floors
        foreach ($dungeonRoute->dungeon->floors as $floor) {
            /** @var Floor $floor */
            $dungeonRouteThumbnailJob = DungeonRouteThumbnailJob::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'status'           => DungeonRouteThumbnailJob::STATUS_QUEUED,
                'viewport_width'   => $viewportWidth,
                'viewport_height'  => $viewportHeight,
                'image_width'      => $imageWidth,
                'image_height'     => $imageHeight,
                'zoom_level'       => $zoomLevel,
                'quality'          => $quality,
            ]);

            $dungeonRouteThumbnailJob->setRelation('dungeonRoute', $dungeonRoute);
            $dungeonRouteThumbnailJob->setRelation('floor', $floor);

            // Set it for processing in a queue
            ProcessRouteFloorThumbnailCustom::dispatch(
                $dungeonRouteThumbnailJob,
                $dungeonRoute,
                $floor->index
            );

            $result->push($dungeonRouteThumbnailJob);
        }

        return $result;
    }

    public static function getFileName(DungeonRoute $dungeonRoute, int $floorIndex): string
    {
        return sprintf('%s_%s.jpg', $dungeonRoute->public_key, $floorIndex);
    }

    public static function getTargetFilePath(DungeonRoute $dungeonRoute, int $floorIndex, string $targetFolder): string
    {
        return sprintf('%s/%s', $targetFolder, self::getFilename($dungeonRoute, $floorIndex));
    }

    /**
     * {@inheritDoc}
     */
    public function copyThumbnails(DungeonRoute $sourceDungeonRoute, DungeonRoute $targetDungeonRoute): ?Collection
    {
        // If the dungeons don't match then this doesn't make sense
        if (!$sourceDungeonRoute->has_thumbnail || $sourceDungeonRoute->dungeon_id !== $targetDungeonRoute->dungeon_id) {
            return null;
        }

        $result = collect();

        // Copy over all thumbnails
        foreach ($sourceDungeonRoute->dungeonRouteThumbnails as $thumbnail) {
            /** @var DungeonRouteThumbnail $thumbnail */
            if ($thumbnail->custom) {
                // Custom thumbnails are not copied
                continue;
            }

            // Fetch the file from the disk
            $thumbnailData = Storage::disk($thumbnail->file->disk)->get($thumbnail->file->path);

            $copiedThumbnail = $this->attachThumbnailToDungeonRoute(
                $targetDungeonRoute,
                $thumbnail->floor->index,
                self::getTargetFilePath($targetDungeonRoute, $thumbnail->floor->index, self::THUMBNAIL_FOLDER_PATH),
                $thumbnailData
            );

            if ($copiedThumbnail === null) {
                // If we failed to copy the thumbnail, then we don't want to continue
                continue;
            }

            $result->push($copiedThumbnail);
        }

        return $result;
    }

    public function hasThumbnailsGenerated(DungeonRoute $dungeonRoute): bool
    {
        return $dungeonRoute->dungeonRouteThumbnails()->where('custom', false)->count() > 0;
    }

    private function attachThumbnailToDungeonRoute(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        string       $target,
        string       $thumbnailData,
        bool         $isCustom = false,
    ): ?DungeonRouteThumbnail {
        return DB::transaction(function () use ($dungeonRoute, $floorIndex, $isCustom, $target, $thumbnailData, &$result) {
            // Remove existing thumbnail if it exists
            /** @var DungeonRouteThumbnail $existingThumbnail */
            $existingThumbnail = DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)
                ->where('floor_id', $floorIndex)
                ->first();

            $disk ??= config('filesystems.default', 'public');
            /** @var Floor $floor */
            $floor                 = $dungeonRoute->dungeon->floors->where('index', $floorIndex)->firstOrFail();
            $dungeonRouteThumbnail = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'custom'           => $isCustom,
            ]);

            $file = File::create([
                'model_id'    => $dungeonRouteThumbnail->id,
                'model_class' => DungeonRouteThumbnail::class,
                'disk'        => $disk,
                'path'        => $target,
            ]);
            $dungeonRouteThumbnail->update(['file_id' => $file->id]);

            Storage::disk($disk)->put($target, $thumbnailData);

            // Only after the new file was created, we can delete the old one
            if ($existingThumbnail !== null) {
                // Delete like this so that the file is removed, and then in turn removed from the disk
                $existingThumbnail->delete();
            }

            $this->log->doCreateThumbnailSuccess($target, file_exists($target));

            return $dungeonRouteThumbnail;
        });
    }
}
