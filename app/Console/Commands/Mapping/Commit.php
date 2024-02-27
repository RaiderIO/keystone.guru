<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Mapping\MappingCommitLog;
use Illuminate\Console\Command;

class Commit extends Command
{
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:commit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Commits any saved mapping to Git';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->shell([
            sprintf('cd %s; '.
                // Commit current changes
                'git add database/seeders/dungeondata/* && '.
                'git commit -m "Automated commit for mapping updates"; '.
                // Merge development into mapping
                'git checkout . && '.
                'git clean -f && '.
                'git checkout development && '.
                'git pull && '.
                'git checkout mapping && '.
                'git merge development && '.
                // Push all changes
                'git push', base_path()),
        ]);

        // Does not have any real properties aside from a save date
        (new MappingCommitLog())->save();

        return 0;
    }
}
