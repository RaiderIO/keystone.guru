<?php

namespace App\Console\Commands\Scheduler;

use App\Models\DungeonRoute;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteExpiredDungeonRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:deleteexpired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all routes that have expired ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        $dungeonRoutes = DungeonRoute::whereRaw('expires_at < NOW()')
            ->where('expires_at', '!=', 0)
            ->whereNotNull('expires_at')
            ->get();

        // Retrieve all routes and then delete them
        foreach ($dungeonRoutes as $dungeonRoute) {
            /** @var $dungeonRoute DungeonRoute */
            try {
                $dungeonRoute->delete();
            } catch (Exception $ex) {
                Log::channel('scheduler')->error($ex);
            }
        }

        return 0;
    }
}
