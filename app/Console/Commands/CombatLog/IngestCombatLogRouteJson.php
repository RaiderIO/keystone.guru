<?php

namespace App\Console\Commands\CombatLog;

use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Logging\StructuredLogging;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use Auth;
use Exception;
use Illuminate\Console\Command;
use Str;

class IngestCombatLogRouteJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:ingestcombatlogroutejson {filePath} {--temp} {--log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes all .json files in a folder and pulls them through ARC, generating routes for each one of them.';

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

        $temp = (bool)$this->option('temp');
        $log  = (bool)$this->option('log');

        // We are the admin
        Auth::loginUsingId(1);

        return $this->parseCreateRouteCombatLogJsonRecursively($filePath, function (string $filePath) use (
            $combatLogRouteBodyDungeonRouteService,
            $temp,
            $log
        ) {
            if (!Str::endsWith($filePath, '.json')) {
                $this->comment(sprintf('- Skipping file %s', $filePath));

                return 0;
            }

            return $this->ingestCombatLogRouteJson($combatLogRouteBodyDungeonRouteService, $filePath, $temp, $log);
        });
    }

    /**
     * @throws Exception
     */
    private function ingestCombatLogRouteJson(
        CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService,
        string                                     $filePath,
        bool                                       $temp = true,
        bool                                       $log = false,
    ): int {
        $this->info(sprintf('Parsing file %s', $filePath));

        try {
            if (!$log) {
                StructuredLogging::disable();
            }

            $dungeonRoute = $combatLogRouteDungeonRouteService->convertCombatLogRouteToDungeonRoute(
                CombatLogRouteRequestModel::createFromArray(
                    json_decode(file_get_contents($filePath), true)
                )
            );

            if (!$temp) {
                $dungeonRoute->update([
                    'expires_at' => null,
                ]);
            }

            if (!$log) {
                StructuredLogging::enable();
            }

            $this->info(
                sprintf(
                    '- Generated route %s for dungeon %s (%d/%d, %d pulls)',
                    $dungeonRoute->public_key,
                    __($dungeonRoute->dungeon->name, [], 'en_US'),
                    $dungeonRoute->getEnemyForces(),
                    $dungeonRoute->mappingVersion->enemy_forces_required,
                    $dungeonRoute->killZones->count()
                )
            );
        } catch (Exception $e) {
            $this->error(sprintf('Failed to ingest combat log route: %s', $e->getMessage()));

            return 1;
        }

        return 0;
    }

    /**
     * Parse combat logs recursively if $filePath is a folder. $callback is called for each combat log found.
     */
    protected function parseCreateRouteCombatLogJsonRecursively(string $filePath, callable $callback): int
    {
        $result = -1;

        if (is_dir($filePath)) {
            $this->info(sprintf('%s is a dir, parsing all files in the dir..', $filePath));
            foreach (glob(sprintf('%s/*', $filePath)) as $filePath) {
                // While have a successful result, keep parsing
                if (!is_file($filePath)) {
                    continue;
                }

                if (!str_ends_with($filePath, '.json')) {
                    $this->comment(sprintf('Skipping file %s (not a .zip or .txt)', $filePath));
                    continue;
                }

                if (($result = $callback($filePath)) !== 0) {
                    break;
                }
            }
        } else {
            $result = $callback($filePath);
        }

        return $result;
    }
}
