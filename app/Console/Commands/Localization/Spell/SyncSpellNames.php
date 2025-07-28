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
    protected $signature = 'localization:syncspellnames {gameVersion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the names of all spells from Wowhead and updates the localizations.';

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

        $spellNamesByLocale = $wowheadService->getSpellNames($gameVersion);

        foreach ($spellNamesByLocale as $locale => $spellNames) {
            /** @var Collection $spellNames */
            // Get the existing spell names from the localization file and merge with the fetched names
            $existingSpellNames = __('spells', [], $locale);
            if (!is_array($existingSpellNames) || empty($existingSpellNames)) {
                $existingSpellNames = [];
            }

            // Get the keys that are present in the existing array
            // And then merge the new names with the existing ones, updating them
            $newSpellNames = array_replace(
                $existingSpellNames,
                array_intersect_key($spellNames->toArray(), $existingSpellNames)
            );

            ksort($newSpellNames);
            $this->exportTranslations($locale, 'spells.php', $newSpellNames);
        }
    }
}
