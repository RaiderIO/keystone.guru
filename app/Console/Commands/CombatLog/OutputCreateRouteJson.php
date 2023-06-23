<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogDungeonRouteService;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;

class OutputCreateRouteJson extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:outputcreateroutejson {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes a combat log and outputs the result events as a .json file which you can use to create routes using the API.';

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

        return $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($combatLogDungeonRouteService) {
            if (!str_contains($filePath, '.zip')) {
                $this->comment(sprintf('- Skipping file %s', $filePath));
                return 0;
            }

            return $this->outputCreateRouteJson($combatLogDungeonRouteService, $filePath);
        });
    }

    /**
     * @param CombatLogDungeonRouteService $combatLogDungeonRouteService
     * @param string $filePath
     *
     * @return int
     * @throws \Exception
     */
    private function outputCreateRouteJson(CombatLogDungeonRouteService $combatLogDungeonRouteService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        $resultingFile = str_replace(['.txt', '.zip'], '.json', $filePath);

        $result = file_put_contents(
            base_path($resultingFile),
            json_encode($combatLogDungeonRouteService->getCreateRouteBody($filePath), JSON_PRETTY_PRINT)
        );

        if ($result) {
            $this->comment(sprintf('- Wrote request body to %s', $resultingFile));
        } else {
            $this->warn(sprintf('- Unable to write to file %s', $resultingFile));
        }

        return $result > 0 ? 0 : -1;
    }
}
