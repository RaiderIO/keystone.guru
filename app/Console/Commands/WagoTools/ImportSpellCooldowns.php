<?php

namespace App\Console\Commands\WagoTools;

use App\Models\GameVersion\GameVersion;
use App\Service\Spell\Cooldown\SpellCooldownImportServiceInterface;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use Illuminate\Console\Command;

class ImportSpellCooldowns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wagotools:importspellcooldowns
                            {--product=wow : The CDN product to read DB2 data for, e.g. wow, wowt or wow_classic}
                            {--gameVersion=retail : The game version that product is the client of - only those spells are imported}
                            {--build= : A specific game build, e.g. 12.1.0.69214; defaults to the most recent one}
                            {--no-wowhead : Do not ask Wowhead for the cooldown of player spells the build does not know}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports spell base cooldowns, charges and specializations from the game client DB2 tables published on wago.tools.';

    public function handle(SpellCooldownImportServiceInterface $spellCooldownImportService): int
    {
        $product        = (string)$this->option('product');
        $build          = $this->option('build') === null ? null : (string)$this->option('build');
        $gameVersionKey = (string)$this->option('gameVersion');
        $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);

        if ($gameVersion === null) {
            $this->error(sprintf('Unknown game version %s', $gameVersionKey));

            return 1;
        }

        $this->info(sprintf('Importing %s spell cooldowns and specializations from product %s', $gameVersionKey, $product));

        try {
            $result = $spellCooldownImportService->importCooldowns(
                $product,
                $gameVersion->id,
                $build,
                !$this->option('no-wowhead'),
            );
        } catch (WagoToolsDownloadException $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        if ($result === null) {
            $this->error('Nothing was imported - is wago.tools reachable, and does the build exist?');

            return 1;
        }

        $this->info(sprintf(
            'Build %s knows %d of our %d spells; %d cooldowns changed.',
            $result->build,
            $result->presentCount,
            $result->spellCount,
            $result->cooldownUpdatedCount,
        ));

        if ($result->specializationsImported) {
            $this->info(sprintf('%d spells changed specializations.', $result->specializationUpdatedCount));
        } else {
            $this->warn('The build has no talent tables, so specializations were left as they were.');
        }

        $this->info(sprintf('%d cooldowns were filled in from Wowhead.', $result->wowheadFilledCount));

        $this->comment('Run mapping:save to export the new cooldowns and specializations into the seeders.');

        return 0;
    }
}
