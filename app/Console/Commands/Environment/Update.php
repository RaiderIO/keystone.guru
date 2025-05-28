<?php

namespace App\Console\Commands\Environment;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Jobs\RefreshDiscoverCache;
use Illuminate\Console\Command;

class Update extends Command
{
    use ExecutesShellCommands;

    public const COMPILE = [
        'production' => true,
        'local'      => false,
        'mapping'    => true,
        'staging'    => true,
        'testing'    => true,
    ];

    public const COMPILE_AS = [
        'production' => 'production',
        'local'      => 'dev',
        'mapping'    => 'production',
        'staging'    => 'dev',
        'testing'    => 'dev',
    ];

    public const OPTIMIZE = [
        'production' => true,
        'local'      => false,
        'mapping'    => true,
        'staging'    => true,
        'testing'    => false,
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
    protected $signature = 'environment:update';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $appType = config('app.type');

        $this->info(sprintf('Updating Keystone.guru %s environment', $appType));

        // Regenerate IDE helper
        $this->call('clear-compiled');
        $this->call('ide-helper:generate');
        $this->call('ide-helper:meta');

        $this->call('horizon:publish');

        $this->call('migrate', [
            '--database' => 'migrate',
            '--force'    => true,
        ]);

        $this->call('migrate', [
            '--database' => 'combatlog',
            '--path'     => 'database/migrations_combatlog',
            '--force'    => true,
        ]);

        // Drop all caches for all models while we re-seed
        $this->call('modelCache:clear');

        $this->call('db:seed', [
            '--database' => 'migrate',
            '--force'    => true,
        ]);

        // After seed, create a release if necessary
        if ($appType === 'production') {
            $this->call('make:githubrelease');
            // With the release created, pull the latest tag
            $this->shell([
                'git pull',
            ]);
        }

        // User permissions are funky for local environments - tell git to ignore them
        if ($appType === 'local') {
            $this->shell([
                'git config --global --add safe.directory /var/www',
            ]);

            $this->shell([
                // Write current version to file
                'git rev-list HEAD -1 > version',
            ]);
        }

        $this->call('optimize:clear');
        if (self::OPTIMIZE[$appType]) {
            $this->call('optimize');
        }

        $this->call('queue:restart');
        if ($appType !== 'local') {
            $this->call('supervisor:start');
        }

        // Refresh the affix group ease tiers (for a first run to populate the data)
        $this->call('affixgroupeasetiers:refresh');
        // Dispatch the refreshing of the discovery cache - this can take up to 5 minutes and can be done in the background
        RefreshDiscoverCache::dispatch();
        $this->call('keystoneguru:view', ['operation' => 'cache']);

        // Regenerate API docs
        $this->call('l5-swagger:generate', [
            '--all' => true,
        ]);

        $this->call('vendor:publish', [
            '--provider' => 'L5Swagger\L5SwaggerServiceProvider',
        ]);

        // A bit of a nasty hack to fix permission issues
        $this->shell(sprintf('chown www-data:www-data -R %s', base_path('storage')));
        $this->shell(sprintf('chown www-data:www-data -R %s', base_path('bootstrap/cache')));

        return 0;
    }
}
