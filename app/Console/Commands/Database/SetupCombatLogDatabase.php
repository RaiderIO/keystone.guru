<?php

namespace App\Console\Commands\Database;

use RuntimeException;

class SetupCombatLogDatabase extends SetupDatabase
{
    protected $signature   = 'db:setupcombatlog';
    protected $description = 'Create combatlog database and user if missing (AWS bootstrap command)';

    public function handle(): int
    {
        // Dynamically configure connection as root
        $connection = $this->createRootConnection(config('database.connections.combatlog.host'), config('database.connections.combatlog.port'));

        $database = config('database.connections.combatlog.database');
        if (empty($database)) {
            throw new RuntimeException('❌ Combatlog DB name not set in environment.');
        }

        // Create the database if necessary
        $this->createDatabase($connection, $database);

        // Now ensure the user for this exists and has rights to the database
        $username = config('database.connections.combatlog.username');
        $password = config('database.connections.combatlog.password');

        if (empty($username)) {
            throw new RuntimeException('❌ Combatlog DB user\'s username not set in environment.');
        }
        if (empty($password)) {
            throw new RuntimeException('❌ Combatlog DB user\'s password not set in environment.');
        }

        $this->setupUserForDatabase($connection, $database, $username, $password);

        return self::SUCCESS;
    }
}
