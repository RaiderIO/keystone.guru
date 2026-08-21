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
        // --schema-path is not optional: Laravel resolves the squashed schema dump by *connection* name,
        // so on combatlog_migrate it looks for a combatlog_migrate-schema.dump that does not exist,
        // silently skips the dump and runs the incremental migrations against an empty database - which
        // dies on the first one to touch a table the dump was to have created.
        $this->runCommand('migrate', [
            '--database'    => 'combatlog_migrate',
            '--path'        => 'database/migrations_combatlog',
            '--schema-path' => 'database/schema/combatlog-schema.dump',
            '--force'       => true,
        ], $this->output);

        return 0;
    }
}
