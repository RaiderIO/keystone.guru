<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;

class Migrate extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates both databases at once';

    /**
     * @var string
     */
    protected $signature = 'ksg:migrate';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Migrating database...');
        $this->runCommand('migrate', [
            '--database' => 'migrate',
            '--force'    => true,
        ], $this->output);

        $this->info('Migrating combat log database...');
        $this->runCommand('migrate', [
            '--database' => 'combatlog',
            '--path'     => 'database/migrations_combatlog',
            '--force'    => true,
        ], $this->output);

        return 0;
    }
}
