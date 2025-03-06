<?php

namespace App\Console\Commands\Supervisor;

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
    protected $signature = 'supervisor:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '(Re-)starts supervisor related tasks for this instance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('supervisor:stop');

        $appType = config('app.type');
        // Local environments don't call it local, but empty instead
        $appType = $appType === 'local' ? '' : '-' . $appType;

        $this->shell([
            'sudo /usr/bin/supervisorctl reread',
            'sudo /usr/bin/supervisorctl update',
            sprintf('sudo /usr/bin/supervisorctl start laravel-echo-server%s:*', $appType),
            sprintf('sudo /usr/bin/supervisorctl start laravel-horizon%s:*', $appType),
            sprintf('sudo /usr/bin/supervisorctl start swoole%s:*', $appType),
        ]);

        return 0;
    }
}
