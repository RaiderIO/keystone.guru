<?php

namespace App\Console\Commands\Database;

use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class Backup extends Command
{
    use ExecutesShellCommands;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backs up the current database';

    /**
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Backup MySql database if the environment asks for it!
        $backupDir = config('keystoneguru.db_backup_dir');
        if (!empty($backupDir)) {
            $this->info('Backing up MySQL database...');

            $this->shell([
                sprintf('mysqldump --no-tablespaces -u %s -p\'%s\' %s | gzip -9 -c > %s/%s.%s.sql.gz',
                    config('database.connections.migrate.username'),
                    config('database.connections.migrate.password'),
                    config('database.connections.migrate.database'),
                    $backupDir,
                    config('database.connections.migrate.database'),
                    now()->format('Y.m.d-h.i')
                ),
            ]);

            $this->info('Backing up MySQL database OK!');
        } else {
            $this->info('Unable to back up MySQL database - db_backup_dir was not set in environment');
        }

        return 0;
    }
}
