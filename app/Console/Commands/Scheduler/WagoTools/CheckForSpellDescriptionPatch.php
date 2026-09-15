<?php

namespace App\Console\Commands\Scheduler\WagoTools;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\GameVersion\GameVersion;
use App\Service\Spell\Description\SpellDescriptionPatchCheckServiceInterface;

class CheckForSpellDescriptionPatch extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wagotools:checkforspelldescriptionpatch
                            {--product=wow : The CDN product to compare the latest build for, e.g. wow, wowt or wow_classic}
                            {--gameVersion=retail : The game version that product is the client of}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Files a GitHub issue when wago.tools has a newer game build than the one we last imported spell descriptions from.';

    public function handle(SpellDescriptionPatchCheckServiceInterface $spellDescriptionPatchCheckService): int
    {
        return $this->trackTime(function () use ($spellDescriptionPatchCheckService) {
            $product        = (string)$this->option('product');
            $gameVersionKey = (string)$this->option('gameVersion');
            $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);

            if ($gameVersion === null) {
                $this->error(sprintf('Unknown game version %s', $gameVersionKey));

                return 1;
            }

            $spellDescriptionPatchCheckService->checkForPatch($product, $gameVersionKey, $gameVersion->id);

            return 0;
        });
    }
}
