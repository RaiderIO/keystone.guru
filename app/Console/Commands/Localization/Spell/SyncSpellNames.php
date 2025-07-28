<?php

namespace App\Console\Commands\Localization\Spell;

use App\Console\Commands\Localization\Traits\ExportsTranslations;
use App\Models\GameVersion\GameVersion;
use App\Service\Wowhead\WowheadServiceInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncSpellNames extends Command
{
    use ExportsTranslations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:syncnpcnames {gameVersion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the names of all NPCs from Wowhead and updates the localizations.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(WowheadServiceInterface $wowheadService): void
    {
        $gameVersionKey = $this->argument('gameVersion');
        $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);

        $npcNamesByLocale = $wowheadService->getNpcNames($gameVersion);

        foreach ($npcNamesByLocale as $locale => $npcNames) {
            /** @var Collection $npcNames */
            // Get the existing NPC names from the localization file and merge with the fetched names
            $existingNpcNames = __('npcs', [], $locale);

            // Get the keys that are present in the existing array
            // And then merge the new names with the existing ones, updating them
            $newNpcNames = array_replace(
                $existingNpcNames,
                array_intersect_key($npcNames->toArray(), $existingNpcNames)
            );

            ksort($newNpcNames);
            $this->exportTranslations($locale, 'npcs.php', $newNpcNames);
        }
    }
}
