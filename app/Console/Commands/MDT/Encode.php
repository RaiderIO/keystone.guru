<?php

namespace App\Console\Commands\MDT;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class Encode extends Command
{
    use ConvertsMDTStrings;
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:encode {string}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encodes an MDT string from a json string';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info($this->encode($this->argument('string')));
    }
}
