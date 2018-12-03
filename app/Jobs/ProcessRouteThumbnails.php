<?php

namespace App\Jobs;

use App\Models\DungeonRoute;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessRouteThumbnails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        //
    }
}
