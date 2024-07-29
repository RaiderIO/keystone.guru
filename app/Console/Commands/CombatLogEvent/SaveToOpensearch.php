<?php

namespace App\Console\Commands\CombatLogEvent;

use App\Logging\StructuredLogging;
use App\Models\CombatLog\CombatLogEvent;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataServiceInterface;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class SaveToOpensearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlogevent:opensearch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Saves all CombatLogEvent data to Opensearch.";

    /**
     * Execute the console command.
     */
    public function handle(ChallengeModeRunDataServiceInterface $challengeModeRunDataService): int
    {
        // We don't care for logging atm, we got a progress bar baby
        StructuredLogging::disable();

        $count       = CombatLogEvent::count();
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setFormat(ProgressBar::FORMAT_DEBUG);

        $result = $challengeModeRunDataService->insertAllToOpensearch(1000, function (array $ids) use (&$progressBar) {
            $progressBar->advance(count($ids));
        });

        $progressBar->finish();

        return $result;
    }
}
