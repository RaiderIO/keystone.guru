<?php

namespace App\Console\Commands\Database;

use RuntimeException;

class SetupMainDatabase extends SetupDatabase
{
    protected $signature   = 'db:setupmain';
    protected $description = 'Create main database user if missing (AWS bootstrap command)';

    public function handle(): int
    {
        // Dynamically configure connection as root
        $connection = $this->createRootConnection(config('database.connections.mysql.host'), config('database.connections.mysql.port'));

        $database = config('database.connections.mysql.database');
        if (empty($database)) {
            throw new RuntimeException('❌ Main DB name not set in environment.');
        }

        // Create the database if necessary
        $this->createDatabase($connection, $database);

        // Now ensure the user for this exists and has rights to the database
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        if (empty($username)) {
            throw new RuntimeException('❌ Main DB user\'s username not set in environment.');
        }
        if (empty($password)) {
            throw new RuntimeException('❌ Main DB user\'s password not set in environment.');
        }

        $this->setupUserForDatabase($connection, $database, $username, $password);

        return self::SUCCESS;
    }
}
