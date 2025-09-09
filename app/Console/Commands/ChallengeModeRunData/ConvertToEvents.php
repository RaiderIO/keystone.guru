<?php

namespace App\Console\Commands\ChallengeModeRunData;

use App\Logging\StructuredLogging;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogEvent;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Console\Helper\ProgressBar;

class ConvertToEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'challengemoderundata:convert {--force} {--saveToOpensearch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Takes the contents of the existing challenge_mode_run_data table, corrects the request body, converts the contents to combat log events, and saves those to the database.";

    /**
     * Execute the console command.
     */
    public function handle(ChallengeModeRunDataServiceInterface $challengeModeRunDataService): int
    {
        // We don't care for logging atm, we got a progress bar baby
        StructuredLogging::disable();

        $force = (bool)$this->option('force');
        $saveToOS = (bool)$this->option('saveToOpensearch');

        $count = ChallengeModeRunData::when(!$force, function (Builder $builder) {
            $builder->where('processed', false);
        })->count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setFormat(ProgressBar::FORMAT_DEBUG);

        $result = $challengeModeRunDataService->convert($force,
            function (ChallengeModeRunData $challengeModeRunData) use (
                &$progressBar,
                $saveToOS,
                $challengeModeRunDataService
            ) {
                $progressBar->advance();

                // This immediately saves the data to Opensearch so you can start using it while it's being inserted
                if ($saveToOS) {
                    $challengeModeRunDataService->insertToOpensearch(
                        CombatLogEvent::where('run_id', $challengeModeRunData->run_id)->get()
                    );
                }
            }
        );

        $progressBar->finish();

        return $result;
    }
}
