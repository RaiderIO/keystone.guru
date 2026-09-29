<?php

namespace App\Console\Commands\Localization\Npc;

use App\Console\Commands\Localization\BaseSyncCommand;
use App\Console\Commands\Localization\Traits\ExportsTranslations;
use App\Models\GameVersion\GameVersion;
use App\Service\Wowhead\WowheadTranslationServiceInterface;
use Exception;
use Illuminate\Support\Collection;

class SyncNpcNames extends BaseSyncCommand
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
    public function handle(WowheadTranslationServiceInterface $wowheadService): void
    {
        $gameVersionKey = $this->argument('gameVersion');
        $gameVersion    = GameVersion::firstWhere('key', $gameVersionKey);

        $npcNamesByLocale = $wowheadService->getNpcNames($gameVersion);
        $englishNpcNames  = __('npcs', [], 'en_US');

        foreach ($npcNamesByLocale as $locale => $npcNames) {
            /** @var Collection<int, string> $npcNames */
            $newNpcNames = $this->mergeNpcNames(__('npcs', [], $locale), $englishNpcNames, $npcNames->toArray());

            $this->exportTranslations($locale, 'npcs.php', $newNpcNames);

            if ($this->hasAILanguage($locale)) {
                $this->exportTranslations(sprintf('%s_ai', $locale), 'npcs.php', $newNpcNames);
            }
        }
    }

    /**
     * Overwrites the existing names with the fetched ones for every NPC that en_US knows, so NPCs added to
     * en_US since the last sync are added too. A name the source does not have (empty) is left out, so the
     * English fallback covers it instead of an empty string.
     *
     * @param  array<int, string> $existingNpcNames
     * @param  array<int, string> $englishNpcNames
     * @param  array<int, string> $fetchedNpcNames
     * @return array<int, string>
     */
    public function mergeNpcNames(array $existingNpcNames, array $englishNpcNames, array $fetchedNpcNames): array
    {
        $fetchedNpcNames = array_filter(
            array_intersect_key($fetchedNpcNames, $englishNpcNames),
            static fn(?string $npcName): bool => $npcName !== null && trim($npcName) !== '',
        );

        $newNpcNames = array_replace($existingNpcNames, $fetchedNpcNames);
        ksort($newNpcNames);

        return $newNpcNames;
    }
}
