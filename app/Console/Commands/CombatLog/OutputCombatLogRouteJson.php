<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use Exception;
use Throwable;

class OutputCombatLogRouteJson extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:outputcombatlogroutejson {filePath} {--dungeonOrRaid} {--debugIcons : Ask the Auto Route Creator for debug map icons when this body is posted - only useful when debugging the ARC itself}';

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

        $filePath      = $this->argument('filePath');
        $dungeonOrRaid = (bool)$this->option('dungeonOrRaid');
        $debugIcons    = (bool)$this->option('debugIcons');
        $failed        = false;

        $result = $this->parseCombatLogRecursively($filePath, function (string $filePath) use (
            $combatLogRouteBodyDungeonRouteService,
            $dungeonOrRaid,
            $debugIcons,
            &$failed,
        ) {
            if (!str_ends_with($filePath, '.zip') && !str_ends_with($filePath, '.txt')) {
                $this->comment(sprintf('- Skipping file %s (not a .zip or .txt)', $filePath));

                return 0;
            }

            if (file_exists(self::getResultingFilePath($filePath))) {
                $this->comment(sprintf('- Skipping file %s (already generated .json)', $filePath));

                return 0;
            }

            try {
                return $this->outputCombatLogRouteJson($combatLogRouteBodyDungeonRouteService, $filePath, $dungeonOrRaid, $debugIcons);
            } catch (Throwable $throwable) {
                // One unusable log in a folder must not abort the rest of it - a folder of combat logs routinely
                // contains a run that was never finished, or a single segment of one, neither of which carries the
                // CHALLENGE_MODE_START/END pair a route is built from. The command still exits non-zero.
                $failed = true;

                $this->warn(sprintf('- Unable to parse %s: %s', $filePath, $throwable->getMessage()));

                return 0;
            }
        });

        return $failed ? self::FAILURE : $result;
    }

    /**
     * @throws Exception
     */
    private function outputCombatLogRouteJson(
        CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService,
        string                                     $filePath,
        bool                                       $dungeonOrRaid = false,
        bool                                       $debugIcons = false,
    ): int {
        $this->info(sprintf('Parsing file %s', $filePath));

        $resultingFile = self::getResultingFilePath($filePath);

        $combatLogRouteJson = $combatLogRouteDungeonRouteService->getCombatLogRoute($filePath, $dungeonOrRaid, $debugIcons);
        if ($combatLogRouteJson !== null) {
            $result = file_put_contents(
                $resultingFile,
                json_encode($combatLogRouteJson, JSON_PRETTY_PRINT),
            );

            if ($result) {
                $this->comment(sprintf('- Wrote request body to %s', $resultingFile));
            } else {
                $this->warn(sprintf('- Unable to write to file %s', $resultingFile));
            }
        } else {
            // It's recoverable - we can parse more files if we want to
            $result = 1;

            $this->warn(sprintf('- Unable to parse dungeon route from file %s', $filePath));
        }

        return $result > 0 ? 0 : -1;
    }

    /**
     * The .json request body that a given combat log is written to - alongside the log itself, with its extension
     * swapped out.
     */
    private static function getResultingFilePath(string $filePath): string
    {
        foreach (['.txt', '.zip'] as $extension) {
            if (str_ends_with($filePath, $extension)) {
                return substr($filePath, 0, -strlen($extension)) . '.json';
            }
        }

        return $filePath . '.json';
    }
}
