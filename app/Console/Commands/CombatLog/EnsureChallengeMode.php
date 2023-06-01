<?php

namespace App\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EnsureChallengeMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:ensurechallengemode {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensures that a filepath contains a challenge mode. Otherwise, DELETES the file.';

    /**
     * Execute the console command.
     * @param CombatLogServiceInterface $combatLogService
     * @return int
     */
    public function handle(CombatLogServiceInterface $combatLogService): int
    {
        $filePath = $this->argument('filePath');
        // Assume error
        $result = -1;

        if (is_dir($filePath)) {
            $this->info(sprintf('%s is a dir, parsing all files in the dir..', $filePath));
            foreach (glob(sprintf('%s/*', $filePath)) as $filePath) {
                // While have a successful result, keep parsing
                if (!is_file($filePath)) {
                    continue;
                }

                $result = $this->analyzeCombatLog($combatLogService, $filePath);
                if ($result !== 0) {
                    break;
                }
            }
        } else {
            $result = $this->analyzeCombatLog($combatLogService, $filePath);
        }

        return $result;
    }

    /**
     * @param CombatLogServiceInterface $combatLogService
     * @param string $filePath
     * @return int
     */
    private function analyzeCombatLog(CombatLogServiceInterface $combatLogService, string $filePath): int
    {
        $extractedFilePath = null;
        $this->comment(sprintf('- Analyzing %s', $filePath));

        if (!file_exists($filePath)) {
            $this->error('File does not exist!');
            return -1;
        }

        if (Str::endsWith($filePath, '.zip')) {
            $this->comment('Extracting archive..');
            $zip = new \ZipArchive();
            try {
                $status = $zip->open($filePath);
                if ($status !== true) {
                    $this->error('File is not a valid .zip file');
                    return -2;
                }

                $storageDestinationPath = '/tmp';
                if (!\File::exists($storageDestinationPath)) {
                    \File::makeDirectory($storageDestinationPath, 0755, true);
                }

                $zip->extractTo($storageDestinationPath);

                $extractedFilePath = sprintf('%s/%s.txt', $storageDestinationPath, basename($filePath, '.zip'));
                $this->comment(sprintf('Extracted archive to %s', $extractedFilePath));
            } finally {
                $zip->close();
            }
        }

        $fileToAnalyze = $extractedFilePath ?? $filePath;


        if (($challengeModes = $combatLogService->getChallengeModes($fileToAnalyze))->isEmpty()) {
            $this->info('Does NOT contain challenge modes!');
            $this->removeFile($filePath);
        } else {
            $this->info(sprintf('Contains %d challenge modes. Keeping file.', $challengeModes->count()));
        }

        if (isset($extractedFilePath)) {
            $this->removeFile($extractedFilePath);
        }

        return 0;
    }

    /**
     * @param string $filePath
     * @return bool
     */
    private function removeFile(string $filePath): bool
    {
        if ($result = unlink($filePath)) {
            $this->comment(sprintf('Removed %s successfully', $filePath));
        } else {
            $this->warn(sprintf('Unable to remove %s!', $filePath));
        }

        return $result;
    }
}
