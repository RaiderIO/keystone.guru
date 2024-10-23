<?php

namespace App\Console\Commands\Database;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Release;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;
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
    protected $signature = 'db:backup {--release}';

    /**
     * Execute the console command.
     */
    public function handle(
        ReleaseRepositoryInterface $releaseRepository
    ): int {
        $release = (bool)$this->option('release');

        // If we're not releasing, or we are releasing and the release asks for a backup. Do a backup by default, though, to be sure.
        $latestUnreleasedRelease = $releaseRepository->getLatestUnreleasedRelease();
        if (!$release || ($latestUnreleasedRelease?->backup_db ?? true)) {
            if ($latestUnreleasedRelease instanceof Release) {
                $this->info(sprintf('Backing up MySQL database for release %d...', $latestUnreleasedRelease->id));
            }

            // Backup MySql database if the environment asks for it!
            $backupDir = config('keystoneguru.db_backup_dir');

            if (!empty($backupDir)) {
                $this->info('Backing up MySQL database...');

                $this->shell([
                    sprintf("mysqldump --no-tablespaces --single-transaction --ignore-table=%s.page_views -u %s -p'%s' %s | gzip -9 -c > %s/%s.%s.sql.gz",
                        config('database.connections.migrate.database'),
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
                $this->warn('Unable to back up MySQL database - db_backup_dir was not set in environment');
            }
        } else {
            // $release is true at this point and the latest release backup_db will be false
            $this->info('Skipping backup of MySQL database - latest release does not ask for it');
        }

        return 0;
    }
}
