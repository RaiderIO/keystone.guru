<?php

namespace App\Console\Commands\MDT;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class Encode extends Command
{
    use ExecutesShellCommands;
    use ConvertsMDTStrings;

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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        echo $this->encode($this->argument('string'));
    }
}