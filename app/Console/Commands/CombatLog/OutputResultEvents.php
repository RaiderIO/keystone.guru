<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;

class OutputResultEvents extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:outputresultevents {filePath} {--force=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes a combat log and outputs the result events in a file next to the combat log.';

    /**
     * Execute the console command.
     *
     * @param CombatLogServiceInterface $combatLogService
     *
     * @return int
     * @throws \Exception
     */
    public function handle(CombatLogServiceInterface $combatLogService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');
        $force = (bool)$this->option('force');

        return $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($combatLogService, $force) {
            if (!str_contains($filePath, '.zip')) {
                $this->comment(sprintf('Skipping file %s', $filePath));

                return 0;
            }

            return $this->outputResultEvents($combatLogService, $filePath, $force);
        });
    }

    /**
     * @param CombatLogServiceInterface $combatLogService
     * @param string                    $filePath
     * @param bool                      $force
     * @return int
     */
    private function outputResultEvents(CombatLogServiceInterface $combatLogService, string $filePath, bool $force = false): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        $resultingFile = str_replace(['.txt', '.zip'], '_events.txt', $filePath);

        if (!$force && file_exists($resultingFile)) {
            $this->info(sprintf('Skipping %s - events already generated', $filePath));

            $result = 1;
        } else {
            $resultEvents = $combatLogService->getResultEvents($filePath);
            $result       = file_put_contents($resultingFile, $resultEvents->map(function (BaseResultEvent $resultEvent) {
                // Trim to remove CRLF, implode with PHP_EOL to convert to (most likely) linux line endings
                return trim($resultEvent->getBaseEvent()->getRawEvent());
            })->implode(PHP_EOL));

            if ($result) {
                $this->comment(sprintf('- Wrote %d events to %s', $resultEvents->count(), $resultingFile));
            } else {
                $this->warn(sprintf('- Unable to write to file %s', $resultingFile));
            }
        }

        return $result > 0 ? 0 : -1;
    }
}
