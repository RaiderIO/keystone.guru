<?php

namespace App\Console\Commands\OpenWorldNpc;

use App\Models\Npc\OpenWorldNpc;
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
                            {--batch-size=100 : NPCs per batch; 100 fit one screenshot}
                            {--resolution=2560x1440 : The render resolution of the WoW client the in-game script runs in}
                            {--output-dir= : Where to write the batch, absolute or relative to the project root; defaults to storage/app/open-world-npc-portraits/<timestamp>}
                            {--retry-unrenderable : Also take NPCs an earlier batch could not render}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Writes the next batch of open-world NPCs without a portrait as input for the in-game portrait pipeline.';

    public function handle(OpenWorldNpcPortraitServiceInterface $openWorldNpcPortraitService): int
    {
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

        $outputDirectory = (string)($this->option('output-dir') ?? sprintf('storage/app/open-world-npc-portraits/%s', now()->format('Ymd-His')));
        if (!str_starts_with($outputDirectory, '/')) {
            $outputDirectory = base_path($outputDirectory);
        }

        $export = $openWorldNpcPortraitService->exportBatch(
            $batchSize,
            (int)$resolution[1],
            (int)$resolution[2],
            $outputDirectory,
            (bool)$this->option('retry-unrenderable'),
        );

        if ($export->getNpcs()->isEmpty()) {
            $this->info(sprintf(
                'No open-world NPC is waiting for a portrait; %d wait for a display id',
                $export->getWithoutDisplayIdCount(),
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Exported %d NPCs to %s: npcs.csv, display_ids.csv, display_portraits.lua',
            $export->getNpcs()->count(),
            $export->getOutputDirectory(),
        ));
        $this->info(sprintf('NPC ids: %s', $export->getNpcs()->map(static fn(OpenWorldNpc $npc) => $npc->id)->implode(', ')));
        $this->info(sprintf(
            'The in-game script takes %d screenshot(s) at %dx%d',
            $export->getScreenshotCount(),
            (int)$resolution[1],
            (int)$resolution[2],
        ));
        $this->info(sprintf(
            '%d NPCs remain for later batches; %d wait for a display id',
            $export->getRemainingCount(),
            $export->getWithoutDisplayIdCount(),
        ));

        return self::SUCCESS;
    }
}
