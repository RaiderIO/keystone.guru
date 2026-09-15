<?php

namespace App\Console\Commands\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Service\MDT\MDTMappingExportServiceInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ExportMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:exportmapping {expansion} {gameVersion} {targetFolder}
        {--season= : Only export the dungeons of this season (id) - takes precedence over the expansion argument}
        {--preserveFrom= : Folder holding the current MDT .lua files; content MDT owns and we cannot reproduce is carried over from them}
        {--regenerateMapPOIs : Rebuild map POIs from our own map icons instead of keeping MDT\'s curated ones}
        {--excludeTranslations}
        {--forceEnemyPatrols}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports the current mapping of all dungeons to MDT';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(MDTMappingExportServiceInterface $mappingExportService): int
    {
        $gameVersion = GameVersion::firstWhere('key', $this->argument('gameVersion'));
        if ($gameVersion === null) {
            // Without this the lookup silently returned null and every dungeon fell back to the default
            // game version's mapping instead
            $this->error(sprintf('Unknown game version %s!', $this->argument('gameVersion')));

            return 1;
        }

        $targetFolder = realpath($this->argument('targetFolder'));
        if ($targetFolder === false) {
            $this->error(sprintf('Target folder %s does not exist!', $this->argument('targetFolder')));

            return 1;
        }

        $preserveFromFolder = $this->getPreserveFromFolder();
        if ($preserveFromFolder === false) {
            return 1;
        }

        $excludeTranslations = (bool)$this->option('excludeTranslations');
        $forceEnemyPatrols   = (bool)$this->option('forceEnemyPatrols');
        $regenerateMapPOIs   = (bool)$this->option('regenerateMapPOIs');

        foreach ($this->getDungeons() as $dungeon) {
            if (!Conversion::hasMDTDungeonName($dungeon->key)) {
                $this->warn(sprintf('Unable to find MDT dungeon for key %s!', $dungeon->key));

                continue;
            }

            if (!$dungeon->enemies()->exists()) {
                $this->comment(sprintf('Skipping %s, no enemies found', __($dungeon->name)));

                continue;
            }

            $currentMappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);
            if ($currentMappingVersion === null) {
                $this->comment(sprintf('Skipping %s, no current mapping version found', __($dungeon->name)));

                continue;
            }

            $mdtDungeonName = Conversion::getMDTDungeonName($dungeon->key);
            $preservedFile  = $preserveFromFolder === null ?
                null :
                sprintf('%s/%s.lua', $preserveFromFolder, $mdtDungeonName);

            if ($preservedFile !== null && !file_exists($preservedFile)) {
                $this->warn(sprintf('No existing MDT file found at %s - nothing to preserve', $preservedFile));
                $preservedFile = null;
            }

            $luaString = $mappingExportService->getMDTMappingAsLuaString(
                $currentMappingVersion,
                $excludeTranslations,
                $forceEnemyPatrols,
                $preservedFile,
                $regenerateMapPOIs,
            );

            $fileName = sprintf('%s/%s.lua', $targetFolder, $mdtDungeonName);

            $this->info(sprintf('Saving %s', $fileName));
            file_put_contents($fileName, $luaString);
        }

        $this->info('Done!');

        return 0;
    }

    /**
     * @return Collection<int, Dungeon>
     *
     * @throws Exception
     */
    private function getDungeons(): Collection
    {
        // Passed as a string on the command line, but as an int when called through Artisan::call()
        $seasonOption = $this->option('season');

        if (is_scalar($seasonOption) && (string)$seasonOption !== '') {
            $season = Season::findOrFail((int)$seasonOption);

            $this->info(sprintf('Exporting %d dungeon(s) of season %d', $season->dungeons->count(), $season->id));

            return $season->dungeons;
        }

        $expansion = Expansion::where('shortname', $this->argument('expansion'))->firstOrFail();

        return $expansion->dungeonsAndRaids;
    }

    /**
     * @return string|null|false The folder to preserve MDT owned content from, null if not preserving, false on error.
     */
    private function getPreserveFromFolder(): string|null|false
    {
        $preserveFrom = $this->option('preserveFrom');
        if (!is_string($preserveFrom) || $preserveFrom === '') {
            return null;
        }

        $realPath = realpath($preserveFrom);
        if ($realPath === false) {
            $this->error(sprintf('Preserve folder %s does not exist!', $preserveFrom));

            return false;
        }

        return $realPath;
    }
}
