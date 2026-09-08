<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

trait Bootstrap
{
    private function bootstrap(): void
    {
        $this->migratePhpunitConnection('phpunit', 'migrate');

        $combatlogPhpunitDatabase = config('database.connections.combatlog_phpunit.database');
        if (!empty($combatlogPhpunitDatabase)) {
            $this->migratePhpunitConnection('combatlog_phpunit', 'combatlog_migrate', 'database/migrations_combatlog');
        }
    }

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
