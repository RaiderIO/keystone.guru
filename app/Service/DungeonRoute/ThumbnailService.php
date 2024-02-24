<?php


namespace App\Service\DungeonRoute;

use App\Jobs\ProcessRouteFloorThumbnail;
use App\Jobs\ProcessRouteFloorThumbnailCustom;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Models\Floor\Floor;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Symfony\Component\Process\Process;

class ThumbnailService implements ThumbnailServiceInterface
{
    public const THUMBNAIL_FOLDER_PATH = '/images/route_thumbnails';

    public const THUMBNAIL_CUSTOM_FOLDER_PATH = '/images/route_thumbnails_custom';

    /**
     * @inheritDoc
     */
    public function createThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts = 0): bool
    {
        return $this->doCreateThumbnail(
            $dungeonRoute,
            $floorIndex,
            self::THUMBNAIL_FOLDER_PATH,
        );
    }

    /**
     * @inheritDoc
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
        ?int         $quality = null): bool
    {
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
    }

    /**
     * @param int|null $viewportWidth
     * @param int|null $viewportHeight
     * @param int|null $imageWidth
     * @param int|null $imageHeight
     * @param int|null $zoomLevel
     * @param int|null $quality
     * @return bool
     */
    private function doCreateThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        string       $targetFolder,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?int         $zoomLevel = null,
        ?int         $quality = null): bool
    {
        if (app()->isDownForMaintenance()) {
            Log::channel('scheduler')->info('Not generating thumbnail - app is down for maintenance');

            return false;
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

        $tmpFile = sprintf('/tmp/%s', $filename);
        $target  = self::getTargetFilePath($dungeonRoute, $floorIndex, $targetFolder);

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
                'floorindex'   => $floorIndex,
                'secret'       => config('keystoneguru.thumbnail.preview_secret'),
                'zoomLevel'    => $zoomLevel,
            ]),
            // Second argument; where to save the resulting image
            $tmpFile,
            $viewportWidth,
            $viewportHeight,
        ]);

        Log::channel('scheduler')->info($process->getCommandLine());

        $process->run();

        if ($process->isSuccessful()) {
            if (!file_exists($tmpFile)) {
                Log::channel('scheduler')->error('Unable to find generated thumbnail; did puppeteer download Chromium?');
            } else {
                try {
                    // We've updated the thumbnail; make sure the route is updated, so it doesn't get updated anymore
                    $dungeonRoute->thumbnail_updated_at = \Illuminate\Support\Carbon::now()->toDateTimeString();
                    // Do not update the timestamps of the route! Otherwise, we'll just keep on updating the timestamp
                    $dungeonRoute->timestamps = false;
                    $dungeonRoute->save();

                    // Ensure our write path exists
                    if (!is_dir($targetFolder)) {
                        mkdir($targetFolder, 0755, true);
                    }

                    // Rescale it
                    Log::channel('scheduler')->info(sprintf('Scaling and moving image from %s to %s', $tmpFile, $target));
                    Image::configure(['driver' => 'imagick'])->make($tmpFile)->resize($imageWidth, $imageHeight)->save($target, $quality);

                    // Remove any old .png file that may be there
                    $oldPngFilePath = str_replace('.jpg', '.png', $target);
                    if (file_exists($oldPngFilePath) && unlink($oldPngFilePath)) {
                        Log::channel('scheduler')->info('Removed old .png file');
                    }

                    Log::channel('scheduler')->info(
                        sprintf('Check if %s exists: %s', $target, var_export(file_exists($target), true))
                    );
                    // Image now exists in target location; compress it and move it to the target location
                    // Log::channel('scheduler')->info('Compressing image..');
                    // $this->compressPng($tmpScaledFile, $target);
                } finally {
                    // Cleanup
                    if (file_exists($tmpFile)) {
                        if (unlink($tmpFile)) {
                            Log::channel('scheduler')->info('Removing tmp file success..');
                        } else {
                            Log::channel('scheduler')->warning('Removing tmp file failure!');
                        }
                    }
                    // unlink($tmpScaledFile);
                }
            }
        }

        // Log any errors that may have occurred
        $errors = $process->getErrorOutput();
        if (!empty($errors)) {
            Log::channel('scheduler')->error($errors, [
                'dungeonRoute' => $dungeonRoute->public_key,
                'floor'        => $floorIndex,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function queueThumbnailRefresh(DungeonRoute $dungeonRoute): bool
    {
        $result = false;

        foreach ($dungeonRoute->dungeon->floorsForMapFacade(true)->active()->get() as $floor) {
            /** @var Floor $floor */
            // Set it for processing in a queue
            ProcessRouteFloorThumbnail::dispatch($this, $dungeonRoute, $floor->index);
            $result = true;
        }

        // Temporarily disable timestamps since we don't want this action to update the updated_at
        $dungeonRoute->timestamps                  = false;
        $dungeonRoute->thumbnail_refresh_queued_at = Carbon::now()->toDateTimeString();
        $dungeonRoute->save();

        // Re-enable them
        $dungeonRoute->timestamps = true;

        return $result;
    }

    /**
     * @inheritDoc
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
                $this,
                $dungeonRouteThumbnailJob,
                $dungeonRoute,
                $floor->index
            );

            $result->push($dungeonRouteThumbnailJob);
        }

        return $result;
    }

    /**
     * @return string
     */
    public static function getFileName(DungeonRoute $dungeonRoute, int $floorIndex): string
    {
        return sprintf('%s_%s.jpg', $dungeonRoute->public_key, $floorIndex);
    }

    /**
     * @return string
     */
    public static function getTargetFilePath(DungeonRoute $dungeonRoute, int $floorIndex, string $targetFolder): string
    {
        return public_path(sprintf('%s/%s', $targetFolder, self::getFilename($dungeonRoute, $floorIndex)));
    }

    /**
     * @inheritDoc
     */
    public function copyThumbnails(DungeonRoute $sourceDungeonRoute, DungeonRoute $targetDungeonRoute): bool
    {
        // If the dungeons don't match then this doesn't make sense
        if (!$sourceDungeonRoute->has_thumbnail || $sourceDungeonRoute->dungeon_id !== $targetDungeonRoute->dungeon_id) {
            return false;
        }

        $result = true;

        // Copy over all thumbnails
        foreach ($sourceDungeonRoute->dungeon->floors()->where('facade', 0)->get() as $floor) {
            $sourcePath = static::getTargetFilePath($sourceDungeonRoute, $floor->index, self::THUMBNAIL_FOLDER_PATH);
            $targetPath = static::getTargetFilePath($targetDungeonRoute, $floor->index, self::THUMBNAIL_FOLDER_PATH);

            if (!File::exists($sourcePath) || !File::exists($targetPath)) {
                continue;
            }

            try {
                $result = $result && File::copy($sourcePath, $targetPath);
            } catch (Exception $exception) {
                $result = false;

                logger()->error($exception->getMessage(), [
                    'exception' => $exception,
                ]);
            }
        }

        return $result;
    }

    /**
     * @param DungeonRoute $dungeonRoute
     * @return bool
     */
    public function hasThumbnailsGenerated(DungeonRoute $dungeonRoute): bool
    {
        $result = true;
        foreach ($dungeonRoute->dungeon->floors()->active()->get() as $floor) {
            /** @var Floor $floor */
            $result = $result && file_exists($dungeonRoute->getAbsoluteThumbnailPath($floor->index));
        }

        return $result;
    }
}
