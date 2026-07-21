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
     * The --release flag is kept for backwards compatibility with existing deploy scripts; the per-release
     * backup_db opt-out was retired along with the release tables (#3480). Whether a backup actually
     * happens still depends on `db_backup_dir` being configured, which it currently is not in production.
     *
     * @var string
     */
    protected $signature = 'db:backup {--release}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Backup MySql database if the environment asks for it!
        $backupDir = config('keystoneguru.db_backup_dir');

        if (!empty($backupDir)) {
            $this->info('Backing up MySQL database...');

            $this->shell([
                sprintf(
                    "mysqldump --no-tablespaces --single-transaction --ignore-table=%s.page_views -u %s -p'%s' %s | gzip -9 -c > %s/%s.%s.sql.gz",
                    config('database.connections.migrate.database'),
                    config('database.connections.migrate.username'),
                    config('database.connections.migrate.password'),
                    config('database.connections.migrate.database'),
                    $backupDir,
                    config('database.connections.migrate.database'),
                    now()->format('Y.m.d-h.i'),
                ),
            ]);

            $this->info('Backing up MySQL database OK!');
        } else {
            $this->warn('Unable to back up MySQL database - db_backup_dir was not set in environment');
        }

        return 0;
    }
}
