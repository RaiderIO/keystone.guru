<?php

namespace App\Console\Commands\Environment;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class Update extends Command
{
    use ExecutesShellCommands;

    const COMPILE = [
        'live' => true,
        'local' => false,
        'mapping' => true,
        'staging' => true
    ];

    const COMPILE_AS = [
        'live' => 'production',
        'local' => 'dev',
        'mapping' => 'production',
        'staging' => 'dev'
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the environment using the default settings';

    /**
     * @var string
     */
    protected $signature = 'environment:update {environment}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle($environment)
    {
        $this->call('artisan:up');
        $this->call('artisan:down', [
            '--message' => 'Upgrading keystone.guru, we will be back stronger than ever shortly!',
            '--retry'   => 60
        ]);

        $this->shell([
            // Git commands
            'git checkout .',
            'git clean -f',
            'git pull',

            // Update externals
            'npm install',
            'npm audit fix',
            'composer install',
        ]);

        // Regenerate IDE helper
        $this->call('clear-compiled');
        $this->call('ide-helper:generate');
        $this->call('ide-helper:meta');

        //
        $this->shell([
            'composer dump-autoload',

            // Write current version to file
            'git tag | (tail -n 1) > version',
            self::COMPILE[$environment] ? sprintf('npm run %s -- --env.full true', self::COMPILE_AS[$environment]) : null,
        ]);

        $this->call('horizon:publish');

        $this->call('migrate', [
            '--database' => 'migrate',
            '--force'
        ]);

        $this->call('db:seed', [
            '--database' => 'migrate',
            '--force'
        ]);

        $this->call('optimize:clear');
        $this->call('route:cache');
        $this->call('config:clear');
        $this->call('queue:restart');
        $this->call('keystoneguru:startsupervisor');

        $this->call('up');

        return 0;
    }
}
