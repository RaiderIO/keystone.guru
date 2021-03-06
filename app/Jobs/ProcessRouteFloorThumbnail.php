<?php

namespace App\Jobs;

use App\Models\DungeonRoute;
use App\Models\Floor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Symfony\Component\Process\Process;

class ProcessRouteFloorThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CompressesImages;

    /** @var DungeonRoute $model */
    public DungeonRoute $model;

    /** @var int $floorIndex */
    private int $floorIndex;

    /**
     * Create a new job instance.
     *
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     * @return void
     */
    public function __construct(DungeonRoute $dungeonRoute, int $floorIndex)
    {
        $this->queue = sprintf('%s-%s-thumbnail', env('APP_TYPE'), env('APP_ENV'));
        $this->model = $dungeonRoute;
        $this->floorIndex = $floorIndex;
    }

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     * @return string Get the name of the file this job is generating (without a path!).
     */
    private static function _getFilename(DungeonRoute $dungeonRoute, int $floorIndex): string
    {
        return sprintf('%s_%s.png', $dungeonRoute->public_key, $floorIndex);
    }

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     * @return string The eventual path to the file that this job generates.
     */
    private static function _getTargetFilePath(DungeonRoute $dungeonRoute, int $floorIndex): string
    {
        return public_path(sprintf('images/route_thumbnails/%s', self::_getFilename($dungeonRoute, $floorIndex)));
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('scheduler')->info(sprintf('Started processing %s:%s', $this->model->public_key, $this->floorIndex));

        // 1. Headless chrome saves file in a temp location
        // 2. File is downsized to a smaller thumbnail (can't make the browser window smaller since that'd mess up the image)
        // 3. Moved to public folder

        $filename = self::_getFilename($this->model, $this->floorIndex);

        $tmpFile = sprintf('/tmp/%s', $filename);
        $publicPath = public_path('images/route_thumbnails/');
        $target = self::_getTargetFilePath($this->model, $this->floorIndex);

        // puppeteer chromium-browser
        $process = new Process([
            'node',
            // Script to execute
            resource_path('assets/puppeteer/route_thumbnail.js'),
            // First argument; where to navigate
            route('dungeonroute.preview', [
                'dungeonroute' => $this->model->public_key,
                'floorindex'   => $this->floorIndex,
                'secret'       => env('THUMBNAIL_PREVIEW_SECRET')
            ]),
            // Second argument; where to save the resulting image
            $tmpFile
        ]);

        Log::channel('scheduler')->info($process->getCommandLine());

        $process->run();

        if ($process->isSuccessful()) {
            if (!file_exists($tmpFile)) {
                Log::channel('scheduler')->error('Unable to find generated thumbnail; did puppeteer download Chromium?');
            } else {
                try {
                    // We've updated the thumbnail; make sure the route is updated so it doesn't get updated anymore
                    $this->model->thumbnail_updated_at = Carbon::now()->toDateTimeString();
                    // Do not update the timestamps of the route! Otherwise we'll just keep on updating the timestamp
                    $this->model->timestamps = false;
                    $this->model->save();

                    // Ensure our write path exists
                    if (!is_dir($publicPath)) {
                        mkdir($publicPath, 0755, true);
                    }

                    // Rescale it
                    Log::channel('scheduler')->info(sprintf('Scaling and moving image from %s to %s', $tmpFile, $target));
                    Image::make($tmpFile)->resize(192, 128)->save($target);

                    Log::channel('scheduler')->info(
                        sprintf('Check if %s exists: %s', $target, var_export(file_exists($target), true))
                    );
                    // Image now exists in target location; compress it and move it to the target location
                    // Log::channel('scheduler')->info('Compressing image..');
                    // $this->compressPng($tmpScaledFile, $target);
                } finally {
                    Log::channel('scheduler')->info('Removing previous image..');
                    // Cleanup
                    if (file_exists($tmpFile)) {
                        unlink($tmpFile);
                    }
                    // unlink($tmpScaledFile);
                    Log::channel('scheduler')->info('Done');
                }
            }
        }

        // Log any errors that may have occurred
        $errors = $process->getErrorOutput();
        if (!empty($errors)) {
            Log::channel('scheduler')->error($errors);
        }

        // Finished!
        Log::channel('scheduler')->info(sprintf('Finished processing %s:%s', $this->model->public_key, $this->floorIndex));
    }

    /**
     * @param DungeonRoute $dungeonRoute
     * @return bool
     */
    public static function thumbnailsExistsForRoute(DungeonRoute $dungeonRoute): bool
    {
        $result = true;
        // Check for every floor
        foreach ($dungeonRoute->dungeon->floors as $floor) {
            /** @var $floor Floor */
            // If the file does not actually exist where we expect it to be
            if (!file_exists(self::_getTargetFilePath($dungeonRoute, $floor->index))) {
                Log::channel('scheduler')->info(
                    sprintf('Unable to find thumbnail for %s:%s, re-scheduling for processing!', $dungeonRoute->public_key, $floor->index)
                );
                // Does not exist and stop searching
                $result = false;
                break;
            }
        }

        return $result;
    }
}
