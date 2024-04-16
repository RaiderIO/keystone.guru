<?php

namespace App\Console\Commands\ChallengeModeRunData;

use App\Service\ChallengeModeRunData\ChallengeModeRunDataServiceInterface;
use Illuminate\Console\Command;

class ConvertToEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'challengemoderundata:convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Takes the contents of the existing challenge_mode_run_data table and converts the contents to combat log events, and saves those to the database.";

    /**
     * Execute the console command.
     */
    public function handle(ChallengeModeRunDataServiceInterface $challengeModeRunDataService): int
    {
        return $challengeModeRunDataService->convert();
    }
}
