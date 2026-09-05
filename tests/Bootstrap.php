<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;

trait Bootstrap
{
    private function bootstrap(): void
    {
        // The phpunit schema is persistent and pre-seeded (#4346), not recreated per run, so a
        // migration merged after it was last provisioned leaves it stale until someone remembers
        // to run sh/provision-phpunit-db.sh or sh/worktree.sh provision-db by hand (#4419).
        Artisan::call('migrate', ['--database' => 'phpunit', '--force' => true]);

        $combatlogPhpunitDatabase = config('database.connections.combatlog_phpunit.database');
        if (!empty($combatlogPhpunitDatabase)) {
            Artisan::call('migrate', [
                '--database' => 'combatlog_phpunit',
                '--path'     => 'database/migrations_combatlog',
                '--force'    => true,
            ]);
        }
    }
}
