<?php

namespace App\Jobs;

use App\Models\DungeonRoute;
use Folklore\Image\Facades\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ProcessRouteFloorThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CompressesImages;

    /** @var DungeonRoute $model */
    public $model;

    /** @var int $floorIndex */
    private $floorIndex;

    /**
     * Create a new job instance.
     *
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     * @return void
     */
    public function __construct(DungeonRoute $dungeonRoute, $floorIndex)
    {
        $this->model = $dungeonRoute;
        $this->floorIndex = $floorIndex;
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

        $filename = sprintf('%s_%s.png', $this->model->public_key, $this->floorIndex);

        $tmpFile = sprintf('/tmp/%s', $filename);
        $tmpScaledFile = sprintf('/tmp/scaled_%s', $filename);
        $publicPath = public_path('images/route_thumbnails/');
        $target = sprintf('%s/%s', $publicPath, $filename);

        // puppeteer chromium-browser
        $process = new Process(['node',
            // Script to execute
            resource_path('assets/puppeteer/route_thumbnail.js'),
            // First argument; where to navigate
            route('dungeonroute.preview', ['dungeonroute' => $this->model->public_key, 'floorindex' => $this->floorIndex]),
            // Second argument; where to save the resulting image
            $tmpFile
        ]);

        Log::channel('scheduler')->info($process->getCommandLine());

        $process->run();

        if ($process->isSuccessful()) {
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
                Log::channel('scheduler')->info('Scaling image..');
                Image::make($tmpFile, [
                    'width' => 192,
                    'height' => 128
                ])->save($target);

                Log::channel('scheduler')->info('Removing previous image..');
                // Image now exists in target location; compress it and move it to the target location
                // Log::channel('scheduler')->info('Compressing image..');
                // $this->compressPng($tmpScaledFile, $target);
            } finally {
                // Cleanup
                unlink($tmpFile);
                // unlink($tmpScaledFile);
                Log::channel('scheduler')->info('Done');
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
}
