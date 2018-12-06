<?php

namespace App\Jobs;

use App\Models\DungeonRoute;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ProcessRouteThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var DungeonRoute $dungeonRoute */
    protected $dungeonRoute;

    /**
     * Create a new job instance.
     *
     * @param $dungeonRoute
     * @return void
     */
    public function __construct(DungeonRoute $dungeonRoute)
    {
        $this->dungeonRoute = $dungeonRoute;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::channel('scheduler')->info(sprintf('Started processing %s', $this->dungeonRoute->public_key));

        $queue = \DB::table(config('queue.connections.database.table'))->first();
        if ($queue) {
            $payload = json_decode($queue->payload, true);
            if ($payload['displayName'] === get_class($this)) {
                $obj = unserialize($payload['data']['command']);

                Log::channel('scheduler')->info(var_export($obj, true));
            }
        }

//        // Ensure our write path exists
//        $publicPath = public_path('images/route_thumbnails/');
//        if (!is_dir($publicPath)) {
//            mkdir($publicPath, 0755, true);
//        }
//
//        // puppeteer chromium-browser
//        $process = new Process(['node',
//            // Script to execute
//            resource_path('assets/puppeteer/route_thumbnail.js'),
//            // First argument; where to navigate
//            sprintf('%s/%s/preview', env('APP_URL'), $this->dungeonRoute->public_key),
//            // Second argument; where to save the resulting image
//            $publicPath . sprintf('%s.png', $this->dungeonRoute->public_key)
//        ]);
//
//        Log::channel('scheduler')->info($process->getCommandLine());
//
//        if ($process->isSuccessful()) {
//            // We've updated the thumbnail; make sure the route is updated so it doesn't get updated anymore
//            $this->dungeonRoute->thumbnail_updated_at = Carbon::now()->toDateTimeString();
//            // Do not update the timestamps of the route! Otherwise we'll just keep on updating the timestamp
//            $this->dungeonRoute->timestamps = false;
//            $this->dungeonRoute->save();
//        }
//
//        // Log any errors that may have occurred
//        $errors = $process->getErrorOutput();
//        if (!empty($errors)) {
//            Log::channel('scheduler')->error($errors);
//        }

        // Finished!
        Log::channel('scheduler')->info(sprintf('Finished processing %s', $this->dungeonRoute->public_key));
    }
}
