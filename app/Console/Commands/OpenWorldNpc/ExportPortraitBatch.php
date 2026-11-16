<?php

namespace App\Console\Commands\OpenWorldNpc;

use App\Service\OpenWorldNpc\OpenWorldNpcPortraitServiceInterface;
use Illuminate\Console\Command;

class ExportPortraitBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openworldnpc:exportportraitbatch
                            {--batches=30 : How many batches to write; each is one in-game script and one screenshot}
                            {--batch-size=100 : NPCs per batch; at most what one screenshot holds, 100 at 2560x1440}
                            {--resolution=2560x1440 : The render resolution of the WoW client the in-game script runs in}
                            {--output-dir= : Where to write the batches, absolute or relative to the project root; defaults to storage/app/open-world-npc-portraits/<timestamp>}
                            {--retry-unrenderable : Also take NPCs an earlier batch could not render}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Writes the next batches of open-world NPCs without a portrait as in-game scripts for the portrait pipeline, one screenshot each.';

    public function handle(OpenWorldNpcPortraitServiceInterface $openWorldNpcPortraitService): int
    {
        $batchCount = filter_var($this->option('batches'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($batchCount === false) {
            $this->error('--batches must be a positive whole number');

            return self::FAILURE;
        }

        $batchSize = filter_var($this->option('batch-size'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($batchSize === false) {
            $this->error('--batch-size must be a positive whole number');

            return self::FAILURE;
        }

        if (!preg_match('/^(\d+)x(\d+)$/i', (string)$this->option('resolution'), $resolution)
            || (int)$resolution[1] < 64 || (int)$resolution[2] < 64) {
            $this->error('--resolution must look like 2560x1440 and be at least 64x64');

            return self::FAILURE;
        }

        $resolutionX            = (int)$resolution[1];
        $resolutionY            = (int)$resolution[2];
        $portraitsPerScreenshot = $openWorldNpcPortraitService->getPortraitsPerScreenshot($resolutionX, $resolutionY);
        if ($batchSize > $portraitsPerScreenshot) {
            $this->error(sprintf(
                '--batch-size must be at most %d, the portraits one screenshot at %dx%d holds',
                $portraitsPerScreenshot,
                $resolutionX,
                $resolutionY,
            ));

            return self::FAILURE;
        }

        $outputDirectory = (string)($this->option('output-dir') ?? sprintf('storage/app/open-world-npc-portraits/%s', now()->format('Ymd-His')));
        if (!str_starts_with($outputDirectory, '/')) {
            $outputDirectory = base_path($outputDirectory);
        }

        $export = $openWorldNpcPortraitService->exportBatches(
            $batchSize,
            $batchCount,
            $resolutionX,
            $resolutionY,
            $outputDirectory,
            (bool)$this->option('retry-unrenderable'),
        );

        if ($export->getBatches()->isEmpty()) {
            $this->info('No open-world NPC is waiting for a portrait');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Exported %d NPCs in %d batches to %s: batch_01 to batch_%02d, each with npcs.csv and npc_portraits.lua',
            $export->getNpcCount(),
            $export->getBatches()->count(),
            $export->getOutputDirectory(),
            $export->getBatches()->count(),
        ));
        $this->info(sprintf(
            'Each script takes one screenshot at %dx%d with its BATCH number in the bottom-right corner',
            $resolutionX,
            $resolutionY,
        ));
        $this->info(sprintf('%d NPCs remain for a later export', $export->getRemainingCount()));

        return self::SUCCESS;
    }
}
