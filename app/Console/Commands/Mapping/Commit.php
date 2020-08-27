<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
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
     *
     * @return int
     */
    public function handle()
    {
        $this->shell([
            'git add database/seeds/dungeondata/*',
            'git commit -m "Automated commit for mapping updates"',
            'git push'
        ]);

        return 0;
    }
}
