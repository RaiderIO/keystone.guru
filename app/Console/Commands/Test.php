<?php

namespace App\Console\Commands;

use App\Service\Expansion\ExpansionService;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     */
    public function handle(ExpansionService $expansionService, TimewalkingEventServiceInterface $timewalkingEventService)
    {

        $backupDir = config('keystoneguru.db_backup_dir');
        $this->info(
            sprintf('mysqldump -u %s -p\'%s\' %s | gzip -9 -c > %s/%s.%s.sql.gz',
                config('database.connections.migrate.username'),
                config('database.connections.migrate.password'),
                config('database.connections.migrate.database'),
                $backupDir,
                config('database.connections.migrate.database'),
                now()->format('Y.m.d-h.i')
            ),
        );

        // 'presence-local-route-edit.E2mXPo3'
//        dd($echoServerHttpApiService->getStatus());
//        dd($echoServerHttpApiService->getChannelInfo('presence-local-route-edit.E2mXPo3'));
//        dd($echoServerHttpApiService->getChannelUsers('presence-local-route-edit.E2mXPo3'));
//        dd($echoServerHttpApiService->getChannels());

        return 0;
    }
}
