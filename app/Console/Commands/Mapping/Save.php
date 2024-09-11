<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\CombatLog\CombatLogNpcSpellAssignment;
use App\Models\CombatLog\CombatLogSpellUpdate;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingCommitLog;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Traits\SavesArrayToJsonFile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\ProgressBar;

class Save extends Command
{
    use ExecutesShellCommands;
    use SavesArrayToJsonFile;

    private const PROGRESS_BAR_FORMAT = ' %current%/%max% [%bar%] %percent:3s%% %message%';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Saves the current mapping to a file';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle(): int
    {
        // Drop all caches for all models - otherwise it may produce some strange results
        $this->call('modelCache:clear');

        $dungeonDataDir = database_path('seeders/dungeondata/');
        $combatLogDir   = database_path('seeders/combatlogs/');

        $this->saveMappingVersions($dungeonDataDir);
        $this->saveMappingCommitLogs($dungeonDataDir);
        $this->saveDungeons($dungeonDataDir);
        $this->saveNpcs($dungeonDataDir);
        $this->saveSpells($dungeonDataDir);
        $this->saveCombatlogData($combatLogDir);
        $this->saveDungeonData($dungeonDataDir);

        $mappingBackupDir = config('keystoneguru.mapping_backup_dir');

        // If we should copy the result to another folder..
        if (!empty($mappingBackupDir)) {
            $targetDir = sprintf('%s/%s', $mappingBackupDir, Carbon::now()->format('Y-m-d H:i:s'));

            $tmpZippedFilePath = '/tmp';
            $zippedFileName    = 'mapping.gz';
            $this->info(sprintf('Creating archive of mapping to %s/%s', $tmpZippedFilePath, $zippedFileName));
            $this->shell(sprintf('tar -zcf %s/%s -C %s .', $tmpZippedFilePath, $zippedFileName, $dungeonDataDir));

            $this->info(sprintf('Saving backup of mapping to %s/%s', $targetDir, $zippedFileName));
            $this->shell([
                sprintf('mkdir -p "%s"', $targetDir),
                sprintf('cp -R "%s/%s" "%s"', $tmpZippedFilePath, $zippedFileName, $targetDir),
                sprintf('rm %s/%s', $tmpZippedFilePath, $zippedFileName),
            ]);
        }

        return 0;
    }

    /**
     * @throws \Exception
     */
    private function saveMappingVersions(string $dungeonDataDir): void
    {
        // Save NPC data in the root of folder
        $this->info('Saving mapping versions');

        // Save all mapping versions
        $mappingVersions = MappingVersion::all()
            ->makeVisible(['created_at', 'updated_at']);

        $this->saveDataToJsonFile(
            $mappingVersions->toArray(),
            $dungeonDataDir,
            'mapping_versions.json'
        );
    }

    /**
     * @throws \Exception
     */
    private function saveMappingCommitLogs(string $dungeonDataDir): void
    {
        // Save NPC data in the root of folder
        $this->info('Saving mapping commit logs');

        // Save all mapping versions
        $mappingVersions = MappingCommitLog::all()
            ->makeVisible(['created_at', 'updated_at']);

        $this->saveDataToJsonFile(
            $mappingVersions->toArray(),
            $dungeonDataDir,
            'mapping_commit_logs.json'
        );
    }

    /**
     * @throws \Exception
     */
    private function saveDungeons(string $dungeonDataDir): void
    {
        // Save NPC data in the root of folder
        $this->info('Saving dungeons');

        // Save all dungeons
        $dungeons = Dungeon::without(['expansion', 'gameVersion', 'dungeonSpeedrunRequiredNpcs10Man', 'dungeonSpeedrunRequiredNpcs25Man', 'floors.floorUnions6'])
            ->with(['floors.floorcouplings', 'floors.dungeonSpeedrunRequiredNpcs10Man', 'floors.dungeonSpeedrunRequiredNpcs25Man'])
            ->get();

        $this->saveDataToJsonFile(
            $dungeons->makeVisible([
                'id',
                'expansion_id',
                'zone_id',
                'instance_id',
                'mdt_id',
                'key',
                'name',
                'slug',
                'speedrun_enabled',
            ])
                ->makeHidden(['floor_count'])
                ->toArray(),
            $dungeonDataDir,
            'dungeons.json'
        );
    }

    /**
     * @param string $dungeonDataDir
     * @throws \Exception
     */
    private function saveNpcs(string $dungeonDataDir): void
    {
        // Save NPC data in the root of folder
        $this->info('Saving global NPCs');

        // Save all NPCs which aren't directly tied to a dungeon
        /** @var Collection<Npc> $npcs */
        $npcs = Npc::without(['characteristics', 'spells', 'enemyForces'])
            ->with(['npcCharacteristics', 'npcSpells', 'npcEnemyForces'])
            ->where('dungeon_id', -1)
            ->get()
            ->values();

        $npcs->makeHidden(['type', 'class']);
        foreach ($npcs as $item) {
            $item->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
        }

        $this->saveDataToJsonFile($npcs->toArray(), $dungeonDataDir, 'npcs.json');
    }

    /**
     * @param string $dungeonDataDir
     * @throws \Exception
     */
    private function saveSpells(string $dungeonDataDir): void
    {
        // Save all spells
        $this->info('Saving Spells');

        $spells = Spell::with('spellDungeons')->get();
        foreach ($spells as $spell) {
            $spell->makeHidden(['icon_url'])->makeVisible(['spellDungeons']);
        }

        $this->saveDataToJsonFile($spells->toArray(), $dungeonDataDir, 'spells.json');
    }

    /**
     * @param string $combatlogDir
     * @return void
     * @throws \Exception
     */
    private function saveCombatlogData(string $combatlogDir): void
    {
        // Save all spells
        $this->info('Saving Combatlog data');

        $this->saveDataToJsonFile(CombatLogNpcSpellAssignment::all()->toArray(), $combatlogDir, 'combat_log_npc_spell_assignments.json');
        $this->saveDataToJsonFile(CombatLogSpellUpdate::all()->toArray(), $combatlogDir, 'combat_log_spell_updates.json');
        $this->saveDataToJsonFile(ParsedCombatLog::all()->toArray(), $combatlogDir, 'parsed_combat_logs.json');
    }

    /**
     * @param string $dungeonDataDir
     * @throws \Exception
     */
    private function saveDungeonData(string $dungeonDataDir): void
    {
        // Save all spells
        $this->info('Saving Dungeon data');
        $dungeons = Dungeon::with(['dungeonRoutesForExport'])->get();
        /** @var Dungeon $lastDungeon */
        $lastDungeon = $dungeons->last();

        $this->withProgressBar($dungeons, function (Dungeon $dungeon, ProgressBar $progressBar) use ($dungeonDataDir, $lastDungeon) {
            $progressBar->setFormat(self::PROGRESS_BAR_FORMAT);
            $progressBar->maxSecondsBetweenRedraws(0.1);
            $progressBar->setMessage(__($dungeon->name));


            $rootDirPath = sprintf('%s%s/%s', $dungeonDataDir, $dungeon->expansion->shortname, $dungeon->key);

            $this->saveDungeonDungeonRoutes($dungeon, $rootDirPath);
            $this->saveDungeonNpcs($dungeon, $rootDirPath);

            $floors = $dungeon->floors()->with([
                'enemyPacksForExport',
                'enemyPatrolsForExport',
                'dungeonFloorSwitchMarkersForExport',
                'mapIconsForExport',
                'mountableAreasForExport',
                'floorUnionsForExport',
                'floorUnionAreasForExport',
            ])->get();

            foreach ($floors as $floor) {
                $this->saveFloor($floor, $rootDirPath);
            }

            if ($dungeon->id === $lastDungeon->id) {
                $progressBar->setMessage('Completed!');
            }
        });
        $this->output->writeln('');
    }

    /**
     * @throws \Exception
     */
    private function saveDungeonDungeonRoutes(Dungeon $dungeon, string $rootDirPath): void
    {
        // Demo routes, load it in a specific way to make it easier to import it back in again
        foreach ($dungeon->dungeonRoutesForExport as $demoRoute) {
            /** @var $demoRoute DungeonRoute */
            unset($demoRoute->relations);
            // Do not reload them
            $demoRoute->setAppends([]);
            // Ids cannot be guaranteed with users uploading dungeonroutes as well. As such, a new internal ID must be created
            // for each and every re-import
            $demoRoute->setHidden(['id', 'updated_at', 'thumbnail_refresh_queued_at', 'thumbnail_updated_at', 'unlisted',
                                   'published_at', 'faction', 'specializations', 'classes', 'races', 'affixes',
                                   'expires_at', 'views', 'views_embed', 'popularity', 'pageviews', 'dungeon', 'mappingVersion',
                                   'season']);
            $demoRoute->load(['playerspecializations', 'playerraces', 'playerclasses',
                              'routeattributesraw', 'affixgroups', 'brushlines', 'paths', 'killZones', 'enemyRaidMarkers',
                              'pridefulEnemies', 'mapicons']);

            // Routes and killzone IDs (and dungeonRouteIDs) are not determined by me, users will be adding routes and killzones.
            // I cannot serialize the IDs in the dev environment and expect it to be the same on the production instance
            // Thus, remove the IDs from both Paths and KillZones as we need to make new IDs when the DungeonRoute
            // is imported into the production environment
            $toHide = new Collection();
            // No ->merge() :( -> https://medium.com/@tadaspaplauskas/quick-tip-laravel-eloquent-collections-merge-gotcha-moment-e2a56fc95889
            foreach ($demoRoute->playerspecializations as $item) {
                $toHide->add($item);
            }

            foreach ($demoRoute->playerraces as $item) {
                $toHide->add($item);
            }

            foreach ($demoRoute->playerclasses as $item) {
                $toHide->add($item);
            }

            foreach ($demoRoute->routeattributesraw as $item) {
                $toHide->add($item);
            }

            foreach ($demoRoute->affixgroups as $item) {
                $toHide->add($item);
            }

            foreach ($demoRoute->brushlines as $item) {
                $item->setVisible(['floor_id', 'polyline']);
                $toHide->add($item);
            }

            foreach ($demoRoute->paths as $item) {
                $item->load(['linkedawakenedobelisks']);
                $item->setVisible(['floor_id', 'polyline', 'linkedawakenedobelisks']);
                $toHide->add($item);
            }

            foreach ($demoRoute->killZones as $item) {
                // Hidden by default to save data
                $item->makeVisible(['floor_id']);
                $toHide->add($item);
            }

            foreach ($demoRoute->enemyRaidMarkers as $item) {
                $toHide->add($item);
            }

            foreach ($demoRoute->pridefulEnemies as $item) {
                $toHide->add($item);
            }

            foreach ($demoRoute->mapicons as $item) {
                $item->load(['linkedawakenedobelisks']);
                $item->setVisible([
                    'floor_id',
                    'map_icon_type_id',
                    'lat',
                    'lng',
                    'comment',
                    'permanent_tooltip',
                    'seasonal_index',
                    'linkedawakenedobelisks',
                ]);
                $toHide->add($item);
            }

            foreach ($toHide as $item) {
                /** @var $item Model */
                $item->makeHidden(['id', 'dungeon_route_id']);
            }
        }

//        if ($dungeon->dungeonRoutesForExport->isNotEmpty()) {
//            $this->info(sprintf('-- Saving %s dungeonroutes', $dungeon->dungeonRoutesForExport->count()));
//        }

        $this->saveDataToJsonFile($dungeon->dungeonRoutesForExport->toArray(), $rootDirPath, 'dungeonroutes.json');
    }

    /**
     * @throws \Exception
     */
    private function saveDungeonNpcs(Dungeon $dungeon, string $rootDirPath): void
    {
        $npcs = Npc::without(['characteristics', 'spells', 'enemyForces'])
            ->with(['npcCharacteristics', 'npcSpells', 'npcEnemyForces'])
            ->where('dungeon_id', $dungeon->id)
            ->get()
            ->makeHidden(['type', 'class'])
            ->values();

        foreach ($npcs as $item) {
            $item->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
        }

        // Save NPC data in the root of the dungeon folder
//        if ($npcs->count() > 0) {
//            $this->info(sprintf('-- Saving %s npcs', $npcs->count()));
//        }

        $this->saveDataToJsonFile($npcs, $rootDirPath, 'npcs.json');
    }

    /**
     * @throws \Exception
     */
    private function saveFloor(Floor $floor, string $rootDirPath): void
    {
//        $this->info(sprintf('-- Saving floor %s', __($floor->name)));
        // Only export NPC->id, no need to store the full npc in the enemy
        $enemies      = $floor->enemiesForExport()->without(['npc', 'type'])->get()->makeVisible(['mdt_scale'])->values();
        $enemyPacks   = $floor->enemyPacksForExport->values();
        $enemyPatrols = $floor->enemyPatrolsForExport->values();
        /** @var \Illuminate\Database\Eloquent\Collection $dungeonFloorSwitchMarkers */
        $dungeonFloorSwitchMarkers = $floor->dungeonFloorSwitchMarkersForExport->values();
        // floorCouplingDirection is an attributed column which does not exist in the database; it exists in the DungeonData seeder
        $dungeonFloorSwitchMarkers
            ->makeHidden(['floorCouplingDirection'])
            ->map(static function (DungeonFloorSwitchMarker $dungeonFloorSwitchMarker) {
                $dungeonFloorSwitchMarker->direction = $dungeonFloorSwitchMarker->direction === '' ?
                    null : $dungeonFloorSwitchMarker->direction;

                return $dungeonFloorSwitchMarker;
            });

        /** @var \Illuminate\Database\Eloquent\Collection $mapIcons */
        $mapIcons        = $floor->mapIconsForExport->values();
        $mountableAreas  = $floor->mountableAreasForExport->values();
        $floorUnions     = $floor->floorUnionsForExport()->without(['floorUnionAreas'])->get()->values();
        $floorUnionAreas = $floor->floorUnionAreasForExport->values();

        // Map icons can ALSO be added by users, thus we never know where this thing comes. As such, insert it
        // at the end of the table instead.
        $mapIcons->makeHidden(['id', 'linked_awakened_obelisk_id']);

        $result['enemies']                      = $enemies;
        $result['enemy_packs']                  = $enemyPacks;
        $result['enemy_patrols']                = $enemyPatrols;
        $result['dungeon_floor_switch_markers'] = $dungeonFloorSwitchMarkers;
        $result['map_icons']                    = $mapIcons;
        $result['mountable_areas']              = $mountableAreas;
        $result['floor_unions']                 = $floorUnions;
        $result['floor_union_areas']            = $floorUnionAreas;

        foreach ($result as $category => $categoryData) {
            // Save enemies, packs, patrols, markers on a per-floor basis
//            if ($categoryData->count() > 0) {
//                $this->info(sprintf('--- Saving %s %s', $categoryData->count(), $category));
//            }

            $this->saveDataToJsonFile($categoryData, sprintf('%s/%s', $rootDirPath, $floor->index), sprintf('%s.json', $category));
        }
    }
}
