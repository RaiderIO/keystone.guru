<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor;
use App\Models\MapIcon;
use App\Models\Mapping\MappingVersion;
use App\Models\MountableArea;
use App\Models\Npc;
use App\Models\Spell;
use App\Traits\SavesArrayToJsonFile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Save extends Command
{
    use SavesArrayToJsonFile;
    use ExecutesShellCommands;

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
     *
     * @return int
     */
    public function handle()
    {
        // Drop all caches for all models - otherwise it may produce some strange results
        $this->call('modelCache:clear');

        $dungeonDataDir = database_path('/seeders/dungeondata/');

        $this->saveMappingVersions($dungeonDataDir);
        $this->saveDungeons($dungeonDataDir);
        $this->saveNpcs($dungeonDataDir);
        $this->saveSpells($dungeonDataDir);
        $this->saveDungeonData($dungeonDataDir);

        $mappingBackupDir = config('keystoneguru.mapping_backup_dir');

        // If we should copy the result to another folder..
        if (!empty($mappingBackupDir)) {
            $targetDir = sprintf('%s/%s', $mappingBackupDir, Carbon::now()->format('Y-m-d H:i:s'));

            $this->info(sprintf('Saving backup of mapping to %s', $targetDir));
            $this->shell([
                sprintf('mkdir -p "%s"', $targetDir),
                sprintf('cp -R "%s" "%s"', $dungeonDataDir, $targetDir),
            ]);
        }

        return 0;
    }

    /**
     * @param string $dungeonDataDir
     */
    private function saveMappingVersions(string $dungeonDataDir)
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
     * @param string $dungeonDataDir
     */
    private function saveDungeons(string $dungeonDataDir)
    {
        // Save NPC data in the root of folder
        $this->info('Saving dungeons');

        // Save all dungeons
        $dungeons = Dungeon::without(['expansion', 'dungeonspeedrunrequirednpcs'])->with(['floors.floorcouplings', 'floors.dungeonspeedrunrequirednpcs'])->get();

        $this->saveDataToJsonFile(
            $dungeons->makeVisible([
                'id',
                'expansion_id',
                'zone_id',
                'mdt_id',
                'key',
                'name',
                'slug',
                'enemy_forces_required',
                'enemy_forces_required_teeming',
                'timer_max_seconds',
                'speedrun_enabled',
            ])->toArray(),
            $dungeonDataDir,
            'dungeons.json'
        );
    }

    /**
     * @param $dungeonDataDir string
     */
    private function saveNpcs(string $dungeonDataDir)
    {
        // Save NPC data in the root of folder
        $this->info('Saving global NPCs');

        // Save all NPCs which aren't directly tied to a dungeon
        $npcs = Npc::without(['spells'])->with(['npcspells'])->where('dungeon_id', -1)->get()->values();
        $npcs->makeHidden(['type', 'class']);
        foreach ($npcs as $item) {
            $item->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
        }

        $this->saveDataToJsonFile($npcs->toArray(), $dungeonDataDir, 'npcs.json');
    }

    /**
     * @param $dungeonDataDir string
     */
    private function saveSpells(string $dungeonDataDir)
    {
        // Save all spells
        $this->info('Saving Spells');

        $spells = Spell::all();
        foreach ($spells as $spell) {
            $spell->makeHidden(['icon_url']);
        }
        $this->saveDataToJsonFile($spells->toArray(), $dungeonDataDir, 'spells.json');
    }

    /**
     * @param $dungeonDataDir string
     */
    private function saveDungeonData(string $dungeonDataDir)
    {
        foreach (Dungeon::all() as $dungeon) {
            /** @var $dungeon Dungeon */
            $this->info(sprintf('- Saving dungeon %s', __($dungeon->name)));

            $rootDirPath = sprintf('%s%s/%s', $dungeonDataDir, $dungeon->expansion->shortname, $dungeon->key);

            $this->saveDungeonDungeonRoutes($dungeon, $rootDirPath);
            $this->saveDungeonNpcs($dungeon, $rootDirPath);

            /** @var Dungeon $dungeon */
            foreach ($dungeon->floors as $floor) {
                $this->saveFloor($floor, $rootDirPath);
            }
        }
    }

    /**
     * @param Dungeon $dungeon
     * @param string $rootDirPath
     * @return void
     */
    private function saveDungeonDungeonRoutes(Dungeon $dungeon, string $rootDirPath): void
    {
        // Demo routes, load it in a specific way to make it easier to import it back in again
        $demoRoutes = $dungeon->dungeonroutes->where('demo', true)->values();
        foreach ($demoRoutes as $demoRoute) {
            /** @var $demoRoute DungeonRoute */
            unset($demoRoute->relations);
            // Do not reload them
            $demoRoute->setAppends([]);
            // Ids cannot be guaranteed with users uploading dungeonroutes as well. As such, a new internal ID must be created
            // for each and every re-import
            $demoRoute->setHidden(['id', 'thumbnail_refresh_queued_at', 'thumbnail_updated_at', 'unlisted', 'published_at',
                                   'faction', 'specializations', 'classes', 'races', 'affixes', 'expires_at', 'views', 'popularity', 'pageviews']);
            $demoRoute->load(['playerspecializations', 'playerraces', 'playerclasses',
                              'routeattributesraw', 'affixgroups', 'brushlines', 'paths', 'killzones', 'enemyraidmarkers',
                              'pridefulenemies', 'mapicons']);

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
            foreach ($demoRoute->killzones as $item) {
                // Hidden by default to save data
                $item->makeVisible(['floor_id']);
                $toHide->add($item);
            }
            foreach ($demoRoute->enemyraidmarkers as $item) {
                $toHide->add($item);
            }
            foreach ($demoRoute->pridefulenemies as $item) {
                $toHide->add($item);
            }
            foreach ($demoRoute->mapicons as $item) {
                $item->load(['linkedawakenedobelisks']);
                $item->setVisible([
                    'mapping_version_id',
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

        if ($demoRoutes->count() > 0) {
            $this->info(sprintf('-- Saving %s dungeonroutes', $demoRoutes->count()));
        }
        $this->saveDataToJsonFile($demoRoutes->toArray(), $rootDirPath, 'dungeonroutes.json');
    }

    /**
     * @param Dungeon $dungeon
     * @param string $rootDirPath
     * @return void
     */
    private function saveDungeonNpcs(Dungeon $dungeon, string $rootDirPath): void
    {
        $npcs = Npc::without(['spells'])->with(['npcspells'])->where('dungeon_id', $dungeon->id)->get()->values();
        $npcs->makeHidden(['type', 'class']);
        foreach ($npcs as $item) {
            $item->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
        }

        // Save NPC data in the root of the dungeon folder
        if ($npcs->count() > 0) {
            $this->info(sprintf('-- Saving %s npcs', $npcs->count()));
        }
        $this->saveDataToJsonFile($npcs, $rootDirPath, 'npcs.json');
    }

    /**
     * @param Floor $floor
     * @param string $rootDirPath
     * @return void
     */
    private function saveFloor(Floor $floor, string $rootDirPath): void
    {
        $this->info(sprintf('-- Saving floor %s', __($floor->name)));
        // Only export NPC->id, no need to store the full npc in the enemy
        $enemies = $floor->enemiesForExport()->without(['npc', 'type'])->with('npc:id')->get()->values();
        foreach ($enemies as $enemy) {
            /** @var $enemy Enemy */
            if ($enemy->npc !== null) {
                $enemy->npc->unsetRelation('spells');
                $enemy->npc->unsetRelation('npcbolsteringwhitelists');
                $enemy->npc->unsetRelation('type');
                $enemy->npc->unsetRelation('class');
            }
        }
        $enemyPacks                = $floor->enemyPacksForExport->values();
        $enemyPatrols              = $floor->enemyPatrolsForExport->values();
        $dungeonFloorSwitchMarkers = $floor->dungeonFloorSwitchMarkersForExport->values();

        // Direction is an attributed column which does not exist in the database; it exists in the DungeonData seeder
        $dungeonFloorSwitchMarkers->makeHidden(['direction']);
        $mapIcons       = $floor->mapIconsForExport->values();
        $mountableAreas = $floor->mountableAreasForExport->values();

        // Map icons can ALSO be added by users, thus we never know where this thing comes. As such, insert it
        // at the end of the table instead.
        $mapIcons->makeHidden(['id', 'linked_awakened_obelisk_id']);

        $result['enemies']                      = $enemies;
        $result['enemy_packs']                  = $enemyPacks;
        $result['enemy_patrols']                = $enemyPatrols;
        $result['dungeon_floor_switch_markers'] = $dungeonFloorSwitchMarkers;
        $result['map_icons']                    = $mapIcons;
        $result['mountable_areas']              = $mountableAreas;

        foreach ($result as $category => $categoryData) {
            // Save enemies, packs, patrols, markers on a per-floor basis
            if ($categoryData->count() > 0) {
                $this->info(sprintf('--- Saving %s %s', $categoryData->count(), $category));
            }
            $this->saveDataToJsonFile($categoryData, sprintf('%s/%s', $rootDirPath, $floor->index), sprintf('%s.json', $category));
        }
    }
}
