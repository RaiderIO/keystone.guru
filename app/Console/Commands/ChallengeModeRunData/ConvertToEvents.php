<?php

namespace App\Console\Commands\ChallengeModeRunData;

use App\Logging\StructuredLogging;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ConvertToEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'challengemoderundata:convert {--force}';

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
        // We don't care for logging atm, we got a progress bar baby
        StructuredLogging::disable();

        $force = (bool)$this->option('force');

        $count = ChallengeModeRunData::when(!$force, function (Builder $builder) {
            $builder->where('processed', false);
        })->count();

        $progressBar = $this->output->createProgressBar($count);

        $result = $challengeModeRunDataService->convert($force, function () use (&$progressBar) {
            $progressBar->advance();
        });

        $progressBar->finish();

        return $result;
    }
}
