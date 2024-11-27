<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use Exception;

class OutputCombatLogRouteJson extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:outputcombatlogroutejson {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes a combat log and outputs the result events as a .json file which you can use to create routes using the API.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(CombatLogRouteDungeonRouteServiceInterface $combatLogRouteBodyDungeonRouteService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');

        return $this->parseCombatLogRecursively($filePath, function (string $filePath) use ($combatLogRouteBodyDungeonRouteService) {
            if (!str_contains($filePath, '.zip')) {
                $this->comment(sprintf('- Skipping file %s', $filePath));

                return 0;
            }

            return $this->outputCombatLogRouteJson($combatLogRouteBodyDungeonRouteService, $filePath);
        });
    }

    /**
     * @throws Exception
     */
    private function outputCombatLogRouteJson(CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService, string $filePath): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        $resultingFile = str_replace(['.txt', '.zip'], '.json', $filePath);

        $result = file_put_contents(
            $resultingFile,
            json_encode($combatLogRouteDungeonRouteService->getCombatLogRoute($filePath), JSON_PRETTY_PRINT)
        );

        if ($result) {
            $this->comment(sprintf('- Wrote request body to %s', $resultingFile));
        } else {
            $this->warn(sprintf('- Unable to write to file %s', $resultingFile));
        }

        return $result > 0 ? 0 : -1;
    }
}
