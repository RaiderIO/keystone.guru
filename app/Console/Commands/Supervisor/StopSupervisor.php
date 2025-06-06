<?php

namespace App\Console\Commands\Supervisor;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class StopSupervisor extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stops supervisor related tasks for this instance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $appType = config('app.type');
        // Local environments don't call it local, but empty instead
        $appType = $appType === 'local' ? '' : '-' . $appType;

        $this->shell([
            sprintf('sudo /usr/bin/supervisorctl stop laravel-echo-server%s:*', $appType),
            sprintf('sudo /usr/bin/supervisorctl stop laravel-horizon%s:*', $appType),
            sprintf('sudo /usr/bin/supervisorctl stop swoole%s:*', $appType),
        ]);

        return 0;
    }
}
