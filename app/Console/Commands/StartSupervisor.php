<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class StartSupervisor extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keystoneguru:startsupervisor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '(Re-)starts supervisor related tasks for this instance';

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
    public function handle()
    {
        $appType = env('APP_TYPE');
        // Local environments don't call it local, but empty instead
        $appType = $appType === 'local' ? '' : '-' . $appType;

        $this->shell([
            'sudo supervisorctl reread',
            'sudo supervisorctl update',
            sprintf('sudo supervisorctl stop laravel-echo-server%s:*', $appType),
            sprintf('sudo supervisorctl start laravel-echo-server%s:*', $appType),
            sprintf('sudo supervisorctl stop laravel-horizon%s:*', $appType),
            sprintf('sudo supervisorctl start laravel-horizon%s:*', $appType)
        ]);

        return 0;
    }
}
