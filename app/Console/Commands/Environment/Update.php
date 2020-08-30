<?php

namespace App\Console\Commands\Environment;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class Update extends Command
{
    use ExecutesShellCommands;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the environment using the default settings';

    /**
     * @var bool True to compile the project when updating, false not to
     */
    protected $compile = true;

    /**
     * @var string How to compile the project, may be either 'dev' or 'production'
     */
    protected $compileAs = 'production';

    /**
     * @var string
     */
    protected $signature = 'update';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
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
            $this->compile ? sprintf('npm run %s -- --env.full true', $this->compileAs) : null,
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
