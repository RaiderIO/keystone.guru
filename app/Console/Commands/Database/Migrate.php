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
     * How the main migrations are run. The connection is the elevated `migrate` rather than the runtime
     * `mysql`, for the same reason the combat log ones use `combatlog_migrate` - see below.
     *
     * @var array<string, string|bool>
     */
    public const array MIGRATE_OPTIONS = [
        '--database' => 'migrate',
        '--force'    => true,
    ];

    /**
     * How the combat log migrations are run, everywhere they are run.
     *
     * The connection is the elevated `combatlog_migrate` rather than the runtime `combatlog`, which is
     * least-privilege and lacks DROP (#3963, #4242) - and it is named here rather than hardcoded on the
     * individual migrations, so that a migration cannot quietly disagree with its caller.
     *
     * `--schema-path` is not optional. Laravel resolves the squashed schema dump by *connection* name, so
     * on `combatlog_migrate` it looks for a `combatlog_migrate-schema.dump` that does not exist. Its
     * loader guards on is_file() and returns silently, so the dump is skipped without an error and the
     * incremental migrations run against an empty database - dying on the first one to touch a table the
     * dump was supposed to have created.
     *
     * @var array<string, string|bool>
     */
    public const array COMBAT_LOG_MIGRATE_OPTIONS = [
        '--database'    => 'combatlog_migrate',
        '--path'        => 'database/migrations_combatlog',
        '--schema-path' => 'database/schema/combatlog-schema.dump',
        '--force'       => true,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Migrating database...');
        $this->runCommand('migrate', self::MIGRATE_OPTIONS, $this->output);

        $this->info('Migrating combat log database...');
        $this->runCommand('migrate', self::COMBAT_LOG_MIGRATE_OPTIONS, $this->output);

        return 0;
    }
}
