<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

abstract class SetupDatabase extends Command
{
    public function createRootConnection(string $host, int $port): Connection
    {
        $this->info('🔧 Connecting as RDS root user...');

        Config::set('database.connections.rds_root', [
            'driver'    => 'mysql',
            'host'      => $host,
            'port'      => $port,
            'database'  => null, // No specific database yet
            'username'  => config('database.connections.migrate.username'), // Root user
            'password'  => config('database.connections.migrate.password'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        return DB::connection('rds_root');
    }

    /**
     * @param Connection $connection
     * @param string     $database
     * @return bool True if the database was created, false if it already existed
     */
    public function createDatabase(Connection $connection, string $database): bool
    {
        $this->info('📦 Checking for database existence...');
        $databaseExists = $connection->selectOne(
            "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?",
            [$database]
        );

        if ($databaseExists === null) {
            $connection->statement(
                sprintf('CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', $database)
            );
            $this->info('✅ Database created.');
        } else {
            $this->info('✅ Database already exists, skipping creation.');
        }

        return $databaseExists === null;
    }

    public function setupUserForDatabase(Connection $connection, string $database, string $username, string $password): int
    {
        $this->info(sprintf("👤 Creating user (if not exists) '%s' for database '%s'...", $username, $database));
        $connection->statement(sprintf("CREATE USER IF NOT EXISTS '%s'@'%%' IDENTIFIED BY '%s';", $username, $password));
        $connection->statement(sprintf("GRANT ALL PRIVILEGES ON %s.* TO '%s'@'%%';", $database, $username));
        $connection->statement('FLUSH PRIVILEGES;');

        $this->info('🎉 DB and user setup completed.');

        return self::SUCCESS;
    }
}
