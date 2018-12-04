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

        // chromium-browser
        $process = new Process(['chromium-browser',
            sprintf(
                '--headless 
                --disable-gpu 
                --window-size=768,512 
                --screenshot="%s"
                --no-sandbox 
                --run-all-compositor-stages-before-draw 
                --virtual-time-budget=%s 
                https://dev.keystone.guru/%s/preview',
                storage_path('route_thumbnails') . DIRECTORY_SEPARATOR . $this->dungeonRoute->public_key . '.png',
                config('keystoneguru.route_thumbnail_virtual_time_budget'),
                $this->dungeonRoute->public_key
            )
        ]);

        $process->run();

        // Log errors to the error output
        if ($process->isSuccessful()) {
            // We've updated the thumbnail; make sure the route is updated so it doesn't get updated anymore
            $this->dungeonRoute->thumbnail_updated_at = Carbon::now()->toDateTimeString();
            $this->dungeonRoute->save();
        }

        // Log any errors that may have occurred
        $errors = $process->getErrorOutput();
        if (!empty($errors)) {
            Log::channel('scheduler')->error($errors);
        }

        // Finished!
        Log::channel('scheduler')->info(sprintf('Finished processing %s', $this->dungeonRoute->public_key));
    }
}
