<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

trait Bootstrap
{
    private function bootstrap(): void
    {
        // The phpunit schema is persistent and pre-seeded (#4346), not recreated per run, so a
        // migration merged after it was last provisioned leaves it stale until someone remembers
        // to run sh/provision-phpunit-db.sh or sh/worktree.sh provision-db by hand (#4419).
        $this->migratePhpunitConnection('phpunit', 'migrate');

        $combatlogPhpunitDatabase = config('database.connections.combatlog_phpunit.database');
        if (!empty($combatlogPhpunitDatabase)) {
            $this->migratePhpunitConnection('combatlog_phpunit', 'combatlog_migrate', 'database/migrations_combatlog');
        }
    }

    /**
     * 2026_05_22_000006_seed_combat_log_observations bypasses --database entirely by hard-coding
     * DB::connection('migrate') / DB::connection('combatlog_migrate') (#4242's "elevated
     * connections" design). Outside an isolated worktree those two connections read the live
     * DB_DATABASE/DB_COMBATLOG_DATABASE, not the phpunit ones, so a migration like that would
     * silently read/write the live dev schemas instead of the test ones. Redirect the elevated
     * connection's database to match the phpunit one for the duration of the migrate call, then
     * put it back - the same trick sh/provision-phpunit-db.sh plays via env overrides, done here
     * via config() because the app is already booted. `url` is cleared on both sides too: a
     * configured DB_URL would otherwise re-override `database` right back (see the identical
     * guard in TestCase::setUp() for the `combatlog` connection).
     */
    private function migratePhpunitConnection(string $testConnection, string $elevatedConnection, ?string $path = null): void
    {
        $testDatabase             = config("database.connections.{$testConnection}.database");
        $originalElevatedDatabase = config("database.connections.{$elevatedConnection}.database");
        $originalElevatedUrl      = config("database.connections.{$elevatedConnection}.url");

        config([
            "database.connections.{$testConnection}.url"          => null,
            "database.connections.{$elevatedConnection}.database" => $testDatabase,
            "database.connections.{$elevatedConnection}.url"      => null,
        ]);
        DB::purge($testConnection);
        DB::purge($elevatedConnection);

        try {
            $options = ['--database' => $testConnection, '--force' => true];
            if ($path !== null) {
                $options['--path'] = $path;
            }

            Artisan::call('migrate', $options);
        } finally {
            config([
                "database.connections.{$elevatedConnection}.database" => $originalElevatedDatabase,
                "database.connections.{$elevatedConnection}.url"      => $originalElevatedUrl,
            ]);
            DB::purge($elevatedConnection);
        }
    }
}
