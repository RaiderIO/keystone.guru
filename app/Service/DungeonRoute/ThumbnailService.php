<?php

namespace App\Service\DungeonRoute;

use App\Jobs\ProcessRouteFloorThumbnail;
use App\Jobs\ProcessRouteFloorThumbnailCustom;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Models\File;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\DungeonRoute\Logging\ThumbnailServiceLoggingInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Random\RandomException;
use Storage;
use Symfony\Component\Process\Process;
use Throwable;

class ThumbnailService implements ThumbnailServiceInterface
{
    public const string THUMBNAIL_FOLDER_PATH = 'thumbnails';

    public const string THUMBNAIL_CUSTOM_FOLDER_PATH = 'thumbnails_custom';

    public function __construct(
        private readonly DungeonRouteRepositoryInterface  $dungeonRouteRepository,
        private readonly ThumbnailServiceLoggingInterface $log,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts = 0,
        string       $variant = DungeonRouteThumbnail::VARIANT_STANDARD,
    ): ?DungeonRouteThumbnail {
        try {
            $this->log->createThumbnailStart($dungeonRoute->public_key, $floorIndex, $attempts);

            // The hero variant is a larger render of the exact same preview page, stored (without downscale)
            // so the wide discovery hero band doesn't show a stretched/pixelated version of the small thumbnail.
            $isHero = $variant === DungeonRouteThumbnail::VARIANT_HERO;

            return $this->doCreateThumbnail(
                $dungeonRoute,
                $floorIndex,
                self::THUMBNAIL_FOLDER_PATH,
                $isHero ? config('keystoneguru.api.dungeon_route.thumbnail.hero_viewport_width') : null,
                $isHero ? config('keystoneguru.api.dungeon_route.thumbnail.hero_viewport_height') : null,
                $isHero ? config('keystoneguru.api.dungeon_route.thumbnail.hero_image_width') : null,
                $isHero ? config('keystoneguru.api.dungeon_route.thumbnail.hero_image_height') : null,
                $isHero ? config('keystoneguru.api.dungeon_route.thumbnail.hero_zoom_level') : null,
                $isHero ? config('keystoneguru.api.dungeon_route.thumbnail.hero_quality') : null,
                $variant,
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
        ?float       $zoomLevel = null,
        ?int         $quality = null,
    ): ?DungeonRouteThumbnail {
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
                $quality ?? config('keystoneguru.api.dungeon_route.thumbnail.default_quality'),
                DungeonRouteThumbnail::VARIANT_STANDARD,
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
        ?float       $zoomLevel = null,
        ?int         $quality = null,
        string       $variant = DungeonRouteThumbnail::VARIANT_STANDARD,
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
                $quality,
            );
            if (app()->isDownForMaintenance()) {
                $this->log->doCreateThumbnailMaintenanceMode();

                return null;
            }

            // Some local dev setups point FILESYSTEM_DISK at the real S3 bucket (so existing
            // thumbnails display correctly), so generating one there would create/delete real
            // production files. Refuse instead of letting local runs mutate remote storage.
            $disk = config('filesystems.default', 'public');
            if ($this->isRemoteDiskUnsafeForLocalGeneration($disk)) {
                $this->log->doCreateThumbnailSkippedRemoteDiskFromLocal();

                return null;
            }

            $viewportWidth ??= config('keystoneguru.api.dungeon_route.thumbnail.default_viewport_width');
            $viewportHeight ??= config('keystoneguru.api.dungeon_route.thumbnail.default_viewport_height');
            $imageWidth ??= config('keystoneguru.api.dungeon_route.thumbnail.default_image_width');
            $imageHeight ??= config('keystoneguru.api.dungeon_route.thumbnail.default_image_height');
            $zoomLevel ??= config('keystoneguru.api.dungeon_route.thumbnail.default_zoom_level');

            // 1. Headless chrome saves file in a temp location
            // 2. File is downsized to a smaller thumbnail (can't make the browser window smaller since that'd mess up the image)
            // 3. Moved to public folder

            $filename = self::getFilename($dungeonRoute, $floorIndex);

            $tmpFile            = sprintf('/tmp/%s_%s', $dungeonRoute->public_key, $filename);
            $tmpFileAfterResize = sprintf('/tmp/%s_resized_%s', $dungeonRoute->public_key, $filename);

            // puppeteer chromium-browser
            $process = new Process([
                'node',
                // Script to execute
                resource_path('assets/puppeteer/route_thumbnail.js'),
                // First argument; where to navigate
                $this->getPreviewUrl($dungeonRoute, $floorIndex, $zoomLevel, $variant),
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
                        $dungeonRoute->thumbnail_updated_at = Carbon::now();
                        // Do not update the timestamps of the route! Otherwise, we'll just keep on updating the timestamp
                        $dungeonRoute->timestamps = false;
                        $dungeonRoute->save();

                        // Rescale it
                        $this->log->doCreateThumbnailRescale($tmpFile, $tmpFileAfterResize);
                        new ImageManager(new ImagickDriver())
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
                            $disk,
                            $targetFolder === self::THUMBNAIL_CUSTOM_FOLDER_PATH,
                            $variant,
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
                        } elseif ($removedTmpFile === false || $removedTmpFileAfterResize === false) {
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

        if ($dungeonRoute->mappingVersion === null) { // @phpstan-ignore identical.alwaysFalse
            $this->log->queueThumbnailRefreshMappingVersionNull($dungeonRoute->public_key);
            // Do not return - we just assume the thumbnail is generated. Otherwise this will keep spamming
            // the logs with this error when it really isn't that important.
        } else {
            /** @var Floor $floor */
            foreach ($dungeonRoute->dungeon->floorsForMapFacade($dungeonRoute->mappingVersion, true)->active()->get() as $floor) {
                // A regular refresh only produces the standard thumbnail. The larger hero variant is expensive
                // and only needed for the handful of routes shown in the discovery hero band, so it is generated
                // separately by the hourly EnsureHeroThumbnails command (see queueHeroThumbnailRefresh).
                ProcessRouteFloorThumbnail::dispatch($dungeonRoute, $floor->index, $force, 0, DungeonRouteThumbnail::VARIANT_STANDARD);
                $result = true;

                $this->log->queueThumbnailRefreshDispatchedJob(
                    $dungeonRoute->public_key,
                    $floor->index,
                    $force,
                );
            }
        }

        // Temporarily disable timestamps since we don't want this action to update the updated_at
        $dungeonRoute->timestamps = false;
        $dungeonRoute->update([
            'thumbnail_refresh_queued_at' => Carbon::now()->toDateTimeString(),
        ]);
        // Re-enable them
        $dungeonRoute->timestamps = true;

        return $result;
    }

    public function queueHeroThumbnailRefresh(DungeonRoute $dungeonRoute, bool $force = false): bool
    {
        if ($dungeonRoute->mappingVersion === null) { // @phpstan-ignore identical.alwaysFalse
            return false;
        }

        // Skip routes whose hero variant already exists and is newer than the route's last content change
        if (!$force && $this->hasFreshHeroThumbnail($dungeonRoute)) {
            return false;
        }

        $result = false;
        /** @var Floor $floor */
        foreach ($dungeonRoute->dungeon->floorsForMapFacade($dungeonRoute->mappingVersion, true)->active()->get() as $floor) {
            ProcessRouteFloorThumbnail::dispatch($dungeonRoute, $floor->index, true, 0, DungeonRouteThumbnail::VARIANT_HERO);
            $result = true;

            $this->log->queueThumbnailRefreshDispatchedJob(
                $dungeonRoute->public_key,
                $floor->index,
                true,
            );
        }

        return $result;
    }

    /**
     * @param  Collection<int, DungeonRoute> $dungeonRoutes
     * @param  bool                          $force
     * @return bool
     */
    public function queueThumbnailRefreshIfMissing(Collection $dungeonRoutes, bool $force = false): bool
    {
        $result = false;

        $dungeonRoutesWithExpiredThumbnails = $this->dungeonRouteRepository->getDungeonRoutesWithExpiredThumbnails(
            $dungeonRoutes,
        );

        foreach ($dungeonRoutesWithExpiredThumbnails as $dungeonRoute) {
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
        ?float       $zoomLevel = null,
        ?int         $quality = null,
    ): Collection {
        $result = collect();

        // Generate thumbnails for _all_ floors
        foreach ($dungeonRoute->dungeon->floors()->active()->get() as $floor) {
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
                $floor->index,
            );

            $result->push($dungeonRouteThumbnailJob);
        }

        return $result;
    }

    public static function getFileName(DungeonRoute $dungeonRoute, int $floorIndex): string
    {
        // Random hash
        try {
            return sprintf(
                '%s_%s_%s.jpg',
                $dungeonRoute->public_key,
                $floorIndex,
                bin2hex(random_bytes(4)),
            );
        } catch (RandomException) {
            return sprintf(
                '%s_%s_%d.jpg',
                $dungeonRoute->public_key,
                $floorIndex,
                time(),
            );
        }
    }

    public static function getTargetFilePath(DungeonRoute $dungeonRoute, int $floorIndex, string $targetFolder): string
    {
        return sprintf('%s/%s/%s', $targetFolder, $dungeonRoute->public_key, self::getFilename($dungeonRoute, $floorIndex));
    }

    /**
     * {@inheritDoc}
     *
     * @return Collection<int, DungeonRouteThumbnail>|null
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
            try {
                $thumbnailData = Storage::disk($thumbnail->file->disk)->get($thumbnail->file->path);

                if ($thumbnailData === null) {
                    // File was linked but contained no data?
                    continue;
                }

                $copiedThumbnail = $this->attachThumbnailToDungeonRoute(
                    $targetDungeonRoute,
                    $thumbnail->floor->index,
                    self::getTargetFilePath($targetDungeonRoute, $thumbnail->floor->index, self::THUMBNAIL_FOLDER_PATH),
                    $thumbnailData,
                    config('filesystems.default', 'public'),
                    false,
                    $thumbnail->variant,
                );

                if ($copiedThumbnail === null) { // @phpstan-ignore identical.alwaysFalse
                    // If we failed to copy the thumbnail, then we don't want to continue
                    continue;
                }

                $result->push($copiedThumbnail);
            } catch (Exception $exception) {
                // Could be thrown if the file does not exist, or if the disk is not available
                $this->log->copyThumbnailsException(
                    $sourceDungeonRoute->public_key,
                    $targetDungeonRoute->public_key,
                    $thumbnail->id,
                    $exception,
                );
            }
        }

        return $result;
    }

    public function hasThumbnailsGenerated(DungeonRoute $dungeonRoute): bool
    {
        return $dungeonRoute->dungeonRouteThumbnails()->where('custom', false)->count() > 0;
    }

    /**
     * A hero thumbnail is fresh when it exists and was rendered at or after the route's last content change.
     * Thumbnail renders intentionally do not bump the route's updated_at, so an edited route reliably reads
     * as stale until the hero variant is regenerated.
     */
    private function hasFreshHeroThumbnail(DungeonRoute $dungeonRoute): bool
    {
        $latestHeroRenderedAt = $dungeonRoute->dungeonRouteThumbnails()
            ->where('custom', false)
            ->where('variant', DungeonRouteThumbnail::VARIANT_HERO)
            ->max('updated_at');

        return $latestHeroRenderedAt !== null
            && Carbon::parse($latestHeroRenderedAt)->greaterThanOrEqualTo($dungeonRoute->updated_at);
    }

    private function attachThumbnailToDungeonRoute(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        string       $target,
        string       $thumbnailData,
        string       $disk,
        bool         $isCustom = false,
        string       $variant = DungeonRouteThumbnail::VARIANT_STANDARD,
    ): DungeonRouteThumbnail {
        return DB::transaction(function () use (
            $dungeonRoute,
            $floorIndex,
            $isCustom,
            $variant,
            $target,
            $thumbnailData,
            $disk,
        ) {
            /** @var Floor $floor */
            $floor = $dungeonRoute->dungeon->floors->where('index', $floorIndex)->firstOrFail();

            /** @var Collection<int, DungeonRouteThumbnail> $existingThumbnailsToDelete */
            $existingThumbnailsToDelete = $isCustom ? collect() : DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)
                // Only replace thumbnails of the same variant so the standard and hero renders don't delete each other
                ->where('variant', $variant)
                // When the target floor is NOT a facade, we want to keep just this floor's thumbnail
                // Routes with a facade will have a thumbnail for the facade, and nothing else, so this query will
                // in that case delete all thumbnails for the route before attaching the new one
                ->when(!$floor->facade, function (Builder $query) use ($floor) {
                    $query->where('floor_id', $floor->id);
                })
                ->get();

            $dungeonRouteThumbnail = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'custom'           => $isCustom,
                'variant'          => $variant,
            ]);

            $file = File::create([
                'model_id'    => $dungeonRouteThumbnail->id,
                'model_class' => DungeonRouteThumbnail::class,
                'disk'        => $disk,
                'path'        => $target,
            ]);
            $dungeonRouteThumbnail->update(['file_id' => $file->id]);

            // Before we create the new thumbnail (which will overwrite the old one), we need to delete the existing thumbnails
            // If we do this after creating we end up deleting the thumbnail we just created
            // There will be a split second where the thumbnail is not available, but that is okay
            foreach ($existingThumbnailsToDelete as $existingThumbnail) {
                // Delete like this so that the file is removed, and then in turn removed from the disk
                $deleteResult = $existingThumbnail->delete();
                $this->log->attachThumbnailToDungeonRouteDeleteExistingThumbnail(
                    $existingThumbnail->id,
                    $existingThumbnail->file?->id,
                    $existingThumbnail->file?->disk,
                    $existingThumbnail->file?->path,
                    $deleteResult,
                );
            }

            Storage::disk($disk)->put($target, $thumbnailData);

            $this->log->attachThumbnailToDungeonRouteSuccess($target, Storage::disk($disk)->exists($target));

            return $dungeonRouteThumbnail;
        });
    }

    /**
     * Whether generating a thumbnail is unsafe: a local environment writing to a remote (S3) disk
     * would create/delete real files on that remote disk.
     */
    private function isRemoteDiskUnsafeForLocalGeneration(string $disk): bool
    {
        $driver = config(sprintf('filesystems.disks.%s.driver', $disk));

        return app()->environment('local') && $driver === 's3';
    }

    /**
     * The URL puppeteer navigates to in order to render the thumbnail. When
     * `keystoneguru.thumbnail.preview_base_url` is configured, the relative preview route is
     * prefixed with it instead of the app's absolute URL, so puppeteer (running inside the app
     * container) can reach a nginx/webserver hostname unreachable via the public APP_URL.
     */
    private function getPreviewUrl(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        ?float       $zoomLevel,
        string       $variant = DungeonRouteThumbnail::VARIANT_STANDARD,
    ): string {
        $params = [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute->public_key,
            'title'        => $dungeonRoute->getTitleSlug(),
            'floorIndex'   => $floorIndex,
            'secret'       => config('keystoneguru.thumbnail.preview_secret'),
            'z'            => $zoomLevel,
            // The large hero render keeps normal-width lines; the small standard render thickens them
            'thicklines' => $variant === DungeonRouteThumbnail::VARIANT_HERO ? 0 : 1,
        ];

        $previewBaseUrl = config('keystoneguru.thumbnail.preview_base_url');
        if ($previewBaseUrl === null) {
            return route('dungeonroute.preview', $params);
        }

        return sprintf('%s%s', $previewBaseUrl, route('dungeonroute.preview', $params, false));
    }
}
