<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class SetupCombatlogDatabase extends Command
{
    protected $signature = 'db:setupcombatlog';
    protected $description = 'Create combatlog database and user if missing (AWS bootstrap command)';

    public function handle(): int
    {
        $this->info('🔧 Connecting as RDS root user...');

        // Dynamically configure connection as root
        Config::set('database.connections.rds_root', [
            'driver'    => 'mysql',
            'host'      => config('database.connections.combatlog.host'),
            'port'      => config('database.connections.combatlog.port'),
            'database'  => null, // No specific database yet
            'username'  => config('database.connections.migrate.username'), // Root user
            'password'  => config('database.connections.migrate.password'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $connection = DB::connection('rds_root');
        $dbName = config('database.connections.combatlog.database');

        $this->info('📦 Checking for combatlog database...');
        $databaseExists = $connection->selectOne(
            "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?",
            [$dbName]
        );

        if ($databaseExists !== null) {
            $this->info('✅ Database already exists, skipping setup.');
            return self::SUCCESS;
        }

        $connection->statement(
            sprintf('CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', $dbName)
        );
        $this->info('✅ Database created.');


        $username = config('database.connections.combatlog.username');
        $password = config('database.connections.combatlog.password');

        if (empty($username)) {
            throw new RuntimeException('❌ Combatlog DB user\'s username not set in environment.');
        }
        if (empty($password)) {
            throw new RuntimeException('❌ Combatlog DB user\'s password not set in environment.');
        }

        $this->info(sprintf("👤 Creating user '%s'...", $username));
        $connection->statement(sprintf("CREATE USER IF NOT EXISTS '%s'@'%%' IDENTIFIED BY '%s';", $username, $password));
        $connection->statement(sprintf("GRANT ALL PRIVILEGES ON %s.* TO '%s'@'%%';", $dbName, $username));
        $connection->statement('FLUSH PRIVILEGES;');

        $this->info('🎉 Combatlog DB and user setup completed.');
        return self::SUCCESS;
    }
}
