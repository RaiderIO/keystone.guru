<?php

namespace App\Console\Commands\Scheduler;

use Exception;

class DeleteExpiredDungeonRoutes extends SchedulerCommand
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
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(): int
    {
        return $this->trackTime(function () {
            $this->deleteExpiredDungeonRoutes();
        });

        return 0;
    }
}
