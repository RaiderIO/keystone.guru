<?php

namespace App\Console\Commands\WagoTools;

use App\Models\GameVersion\GameVersion;
use App\Service\Spell\Description\SpellDescriptionImportServiceInterface;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use Illuminate\Console\Command;

class ImportSpellDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wagotools:importspelldescriptions
                            {--product=wow : The CDN product to read DB2 data for, e.g. wow, wowt or wow_classic}
                            {--gameVersion=retail : The game version that product is the client of - only those spells are imported}
                            {--build= : A specific game build, e.g. 12.1.0.69214; defaults to the most recent one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports spell descriptions from the game client DB2 tables published on wago.tools.';

    public function handle(SpellDescriptionImportServiceInterface $spellDescriptionImportService): int
    {
        $product        = (string)$this->option('product');
        $build          = $this->option('build') === null ? null : (string)$this->option('build');
        $gameVersionKey = (string)$this->option('gameVersion');
        $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);

        if ($gameVersion === null) {
            $this->error(sprintf('Unknown game version %s', $gameVersionKey));

            return 1;
        }

        $this->info(sprintf('Importing %s spell descriptions from product %s', $gameVersionKey, $product));
        $this->comment('The DB2 tables are around 140MB in total - the first run for a build downloads them.');

        $progressBar = null;

        try {
            $result = $spellDescriptionImportService->importDescriptions(
                $product,
                $gameVersion->id,
                $build,
                function (int $handled, int $total) use (&$progressBar): void {
                    $progressBar ??= $this->output->createProgressBar($total);
                    $progressBar->setProgress($handled);
                },
            );
        } catch (WagoToolsDownloadException $exception) {
            $this->error($exception->getMessage());

            return 1;
        } finally {
            $progressBar?->finish();
            $this->newLine();
        }

        if ($result === null) {
            $this->error('Nothing was imported - is wago.tools reachable, and does the build exist?');

            return 1;
        }

        $this->info(sprintf(
            'Rendered %d of %d spells from build %s, %d of which changed.',
            $result->describedCount,
            $result->spellCount,
            $result->build,
            $result->updatedCount,
        ));

        $this->comment('Run mapping:save to export the new descriptions into the seeders.');

        return 0;
    }
}
