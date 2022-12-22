<?php

namespace App\Console\Commands\Environment;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Jobs\RefreshDiscoverCache;
use Illuminate\Console\Command;

class Update extends Command
{
    use ExecutesShellCommands;

    const COMPILE = [
        'live'    => true,
        'local'   => false,
        'mapping' => true,
        'staging' => true,
        'testing' => true,
    ];

    const COMPILE_AS = [
        'live'    => 'production',
        'local'   => 'dev',
        'mapping' => 'production',
        'staging' => 'dev',
        'testing' => 'dev',
    ];

    const OPTIMIZE = [
        'live'    => true,
        'local'   => false,
        'mapping' => true,
        'staging' => true,
        'testing' => false,
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
    public function handle()
    {
        $environment = $this->argument('environment');

        // Regenerate IDE helper
        $this->call('clear-compiled');
        $this->call('ide-helper:generate');
        $this->call('ide-helper:meta');

        $this->call('horizon:publish');

        $this->call('migrate', [
            '--database' => 'migrate',
            '--force'    => true,
        ]);

        // Drop all caches for all models while we re-seed
        $this->call('modelCache:clear');

        $this->call('db:seed', [
            '--database' => 'migrate',
            '--force'    => true,
        ]);

        // After seed, create a release if necessary
        if ($environment === 'live') {
            $this->call('make:githubrelease');
            // With the release created, pull the latest tag
            $this->shell([
                'git pull',
            ]);
        }

        //
        $this->shell([
            // Write current version to file
            'git tag | sort -V | (tail -n 1) > version',
            self::COMPILE[$environment] ? sprintf('npm run %s --env.full true', self::COMPILE_AS[$environment]) : null,
        ]);

        $this->call('optimize:clear');
        if (self::OPTIMIZE[$environment]) {
            $this->call('optimize');
        }
        $this->call('queue:restart');
        $this->call('supervisor:start');

        // Refresh the subcreation ease tiers (for a first run to populate the data)
        $this->call('affixgroupeasetiers:refresh');
        // Dispatch the refreshing of the discovery cache - this can take up to 5 minutes and can be done in the background
        RefreshDiscoverCache::dispatch();
        $this->call('keystoneguru:view', ['operation' => 'cache']);

        // Bit of a nasty hack to fix permission issues
        $this->shell(sprintf('chown www-data:www-data %s/storage/framework/cache/* -R', base_path()));

        return 0;
    }
}
