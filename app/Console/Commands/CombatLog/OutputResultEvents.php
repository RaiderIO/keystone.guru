<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogDungeonRouteService;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;

class OutputResultEvents extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:outputresultevents {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes a combat log and outputs the result events in a file next to the combat log.';

    /**
     * Execute the console command.
     *
     * @param CombatLogDungeonRouteService $combatLogDungeonRouteService
     *
     * @return int
     * @throws \Exception
     */
    public function handle(CombatLogDungeonRouteService $combatLogDungeonRouteService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($combatLogDungeonRouteService)
        {
            return $this->outputResultEvents($combatLogDungeonRouteService, $filePath);
        });
    }

    /**
     * @param CombatLogDungeonRouteService $combatLogDungeonRouteService
     * @param string                       $filePath
     *
     * @return int
     * @throws \Exception
     */
    private function outputResultEvents(CombatLogDungeonRouteService $combatLogDungeonRouteService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        $resultEvents = $combatLogDungeonRouteService->getResultEvents($filePath);

        $resultingFile = str_replace(['.txt', '.zip'], '_events.txt', $filePath);

        $result = file_put_contents(base_path($resultingFile), $resultEvents->map(function (BaseResultEvent $resultEvent)
        {
            return $resultEvent->getBaseEvent()->getRawEvent();
        })->implode(''));

        if ($result) {
            $this->comment(sprintf('- Wrote %d events to %s', $resultEvents->count(), $resultingFile));
        } else {
            $this->warn(sprintf('- Unable to write to file %s', $resultingFile));
        }

        return $result;
    }
}
