<?php

namespace App\Console\Commands\Localization\Npc;

use App\Console\Commands\Localization\BaseSyncCommand;
use App\Console\Commands\Localization\Traits\ExportsTranslations;
use App\Models\Npc\NpcType;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use App\Service\WagoTools\GameLocale;
use App\Service\WagoTools\WagoToolsServiceInterface;

class SyncNpcTypeNames extends BaseSyncCommand
{
    use ExportsTranslations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:syncnpctypenames
                            {--product=wow : The CDN product to read DB2 data for, e.g. wow, wowt or wow_classic}
                            {--build= : A specific game build, e.g. 12.1.0.69214; defaults to the most recent one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the names of all NPC (creature) types from the game data and updates the localizations.';

    /**
     * Execute the console command.
     *
     * @throws WagoToolsDownloadException
     */
    public function handle(WagoToolsServiceInterface $wagoToolsService): int
    {
        $product = (string)$this->option('product');
        $build   = $this->option('build') === null ? null : (string)$this->option('build');
        $build ??= $wagoToolsService->getLatestBuild($product);

        if ($build === null) {
            $this->error(sprintf('Unable to resolve a game build for product %s', $product));

            return 1;
        }

        $englishCreatureTypeNames = $this->readCreatureTypeNames($wagoToolsService, $build, GameLocale::English);

        foreach (GameLocale::translated() as $gameLocale) {
            $locale = $gameLocale->appLocale();

            $npcTypeNames = $this->getNpcTypeNames(
                $englishCreatureTypeNames,
                $this->readCreatureTypeNames($wagoToolsService, $build, $gameLocale),
            );

            $this->exportTranslations($locale, 'npctypes.php', $this->mergeNpcTypeNames($locale, $npcTypeNames));

            if ($this->hasAILanguage($locale)) {
                $aiLocale = sprintf('%s_ai', $locale);
                $this->exportTranslations($aiLocale, 'npctypes.php', $this->mergeNpcTypeNames($aiLocale, $npcTypeNames));
            }
        }

        return 0;
    }

    /**
     * Matches the game's creature types to our NPC types by their English name.
     *
     * @param  array<int|string, string> $englishCreatureTypeNames   creature type ID => English name
     * @param  array<int|string, string> $localizedCreatureTypeNames creature type ID => localized name
     * @return array<string, string>     npctypes translation key => localized name
     */
    public function getNpcTypeNames(array $englishCreatureTypeNames, array $localizedCreatureTypeNames): array
    {
        $result = [];
        foreach ($englishCreatureTypeNames as $creatureTypeId => $englishName) {
            if (!isset(NpcType::ALL[$englishName])) {
                continue;
            }

            $localizedName = trim($localizedCreatureTypeNames[$creatureTypeId] ?? '');
            if ($localizedName === '') {
                continue;
            }

            $result[new NpcType(['type' => $englishName])->type_key] = $localizedName;
        }

        return $result;
    }

    /**
     * Keeps the names the game has no creature type for (Uncategorized), and orders the keys like en_US.
     * A name that is still missing stays absent, so the English fallback covers it.
     *
     * @param  array<string, string> $npcTypeNames
     * @return array<string, string>
     */
    private function mergeNpcTypeNames(string $locale, array $npcTypeNames): array
    {
        // Read from disk: __() would fall back to the English names for a locale that has no file yet
        $filePath      = lang_path(sprintf('%s/npctypes.php', $locale));
        $existingNames = file_exists($filePath) ? include $filePath : [];
        $merged        = array_replace(is_array($existingNames) ? $existingNames : [], $npcTypeNames);

        $result = [];
        foreach (array_keys(__('npctypes', [], 'en_US')) as $key) {
            if (isset($merged[$key]) && $merged[$key] !== '') {
                $result[$key] = $merged[$key];
            }
        }

        return $result;
    }

    /**
     * @return array<int|string, string> creature type ID => name
     *
     * @throws WagoToolsDownloadException
     */
    private function readCreatureTypeNames(WagoToolsServiceInterface $wagoToolsService, string $build, GameLocale $gameLocale): array
    {
        $result = [];
        foreach ($wagoToolsService->readTable('CreatureType', $build, $gameLocale) as $row) {
            $result[$row['ID'] ?? ''] = $row['Name_lang'] ?? '';
        }

        return $result;
    }
}
