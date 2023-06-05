<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExtractUiMapIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:extractuimapids {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps all UI Map IDs that it can find in combat logs from a given file path';

    /**
     * Execute the console command.
     * @param CombatLogServiceInterface $combatLogService
     * @return int
     */
    public function handle(CombatLogServiceInterface $combatLogService): int
    {
        ini_set('memory_limit', '2G');

        $filePath = $this->argument('filePath');
        // Assume error
        $result = -1;

        if (is_dir($filePath)) {
            $this->info(sprintf('%s is a dir, parsing all files in the dir..', $filePath));
            foreach (glob(sprintf('%s/*.zip', $filePath)) as $filePath) {
                // While have a successful result, keep parsing
                if (!is_file($filePath)) {
                    continue;
                }

                $this->info(sprintf('- Parsing %s', $filePath));

                $result = $this->extractUiMapIds($combatLogService, $filePath);
                if ($result !== 0) {
                    break;
                }
            }
        } else {
            $result = $this->extractUiMapIds($combatLogService, $filePath);
        }

        return $result;
    }

    /**
     * @param CombatLogServiceInterface $combatLogService
     * @param string $filePath
     * @return int
     */
    private function extractUiMapIds(CombatLogServiceInterface $combatLogService, string $filePath): int
    {
        if (!file_exists($filePath)) {
            $this->error('File does not exist!');
            return -1;
        }

        if (($uiMapIds = $combatLogService->getUiMapIds($filePath))->isNotEmpty()) {
            foreach ($uiMapIds as $uiMapId => $floorName) {
                $this->info(sprintf('%d: %s', $uiMapId, $floorName));
            }
        }

        return 0;
    }
}
