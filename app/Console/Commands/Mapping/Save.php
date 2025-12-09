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
use App\Models\Traits\HasLatLng;
use App\Models\Traits\HasVertices;
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

    private const string PROGRESS_BAR_FORMAT = ' %current%/%max% [%bar%] %percent:3s%% %message%';

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
            ->makeVisible([
                'created_at',
                'updated_at',
            ]);

        $this->saveDataToJsonFile(
            $mappingVersions->toArray(),
            $dungeonDataDir,
            'mapping_versions.json',
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
            ->makeVisible([
                'created_at',
                'updated_at',
            ]);

        $this->saveDataToJsonFile(
            $mappingVersions->toArray(),
            $dungeonDataDir,
            'mapping_commit_logs.json',
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
        $dungeons = Dungeon::without([
            'expansion',
            'gameVersion',
            'dungeonSpeedrunRequiredNpcs10Man',
            'dungeonSpeedrunRequiredNpcs25Man',
            'floors.floorUnions6',
        ])
            ->with([
                'floors.floorcouplings',
                'floors.dungeonSpeedrunRequiredNpcs10Man',
                'floors.dungeonSpeedrunRequiredNpcs25Man',
            ])
            ->get();

        foreach ($dungeons as $dungeon) {
            foreach ($dungeon->floors as $floor) {
                $floor->makeVisible([
                    'mdt_sub_level',
                    'ui_map_id',
                    'map_name',
                    'active',
                    'enemy_engagement_max_range',
                    'enemy_engagement_max_range_patrols',
                ]);
            }
        }

        $this->saveDataToJsonFile(
            $dungeons->makeVisible([
                'id',
                'expansion_id',
                'game_version_id',
                'zone_id',
                'map_id',
                'instance_id',
                'challenge_mode_id',
                'mdt_id',
                'key',
                'name',
                'slug',
                'raid',
                'heatmap_enabled',
                'speedrun_enabled',
                'speedrun_difficulty_10_man_enabled',
                'speedrun_difficulty_25_man_enabled',
            ])
                ->makeHidden(['floor_count'])
                ->toArray(),
            $dungeonDataDir,
            'dungeons.json',
        );
    }

    /**
     * @param  string     $dungeonDataDir
     * @throws \Exception
     */
    private function saveNpcs(string $dungeonDataDir): void
    {
        // Save NPC data in the root of folder
        $this->info('Saving NPCs');

        // Save all NPCs which aren't directly tied to a dungeon
        /** @var Collection<Npc> $npcs */
        $npcs = Npc::without([
            'characteristics',
            'spells',
            'enemyForces',
        ])
            ->with([
                'npcCharacteristics',
                'npcSpells',
                'npcEnemyForces',
                'npcDungeons',
            ])
            ->get()
            ->values();

        foreach ($npcs as $npc) {
            $npc->makeHidden([
                'type',
                'class',
                'enemy_portrait_url',
            ]);
            $npc->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
            foreach ($npc->npcDungeons as $npcDungeon) {
                $npcDungeon->makeHidden(['dungeon']);
            }
        }

        $this->saveDataToJsonFile($npcs->toArray(), $dungeonDataDir, 'npcs.json');
    }

    /**
     * @param  string     $dungeonDataDir
     * @throws \Exception
     */
    private function saveSpells(string $dungeonDataDir): void
    {
        // Save all spells
        $this->info('Saving Spells');

        $spells = Spell::with('spellDungeons')->get();
        foreach ($spells as $spell) {
            $spell->makeHidden([
                'icon_url',
                'wowhead_url',
            ])->makeVisible(['spellDungeons']);
        }

        $this->saveDataToJsonFile($spells->toArray(), $dungeonDataDir, 'spells.json');
    }

    /**
     * @param  string     $combatlogDir
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
     * @param  string     $dungeonDataDir
     * @throws \Exception
     */
    private function saveDungeonData(string $dungeonDataDir): void
    {
        // Save all spells
        $this->info('Saving Dungeon data');
        $dungeons = Dungeon::with(['dungeonRoutesForExport'])->get();
        /** @var Dungeon $lastDungeon */
        $lastDungeon = $dungeons->last();

        $this->withProgressBar($dungeons, function (Dungeon $dungeon, ProgressBar $progressBar) use (
            $dungeonDataDir,
            $lastDungeon
        ) {
            $progressBar->setFormat(self::PROGRESS_BAR_FORMAT);
            $progressBar->maxSecondsBetweenRedraws(0.1);
            $progressBar->setMessage(__($dungeon->name));

            $rootDirPath = sprintf('%s%s/%s', $dungeonDataDir, $dungeon->expansion->shortname, $dungeon->key);

            $this->saveDungeonDungeonRoutes($dungeon, $rootDirPath);

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
            $demoRoute->setHidden([
                'id',
                'updated_at',
                'thumbnail_refresh_queued_at',
                'thumbnail_updated_at',
                'unlisted',
                'published_at',
                'faction',
                'specializations',
                'classes',
                'races',
                'affixes',
                'expires_at',
                'views',
                'views_embed',
                'popularity',
                'pageviews',
                'dungeon',
                'mappingVersion',
                'season',
                'thumbnails',
            ]);
            $demoRoute->load([
                'playerspecializations',
                'playerraces',
                'playerclasses',
                'routeattributesraw',
                'affixGroups',
                'brushlines',
                'paths',
                'killZones',
                'enemyRaidMarkers',
                'pridefulEnemies',
                'mapicons',
            ]);

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

            foreach ($demoRoute->affixGroups as $item) {
                $toHide->add($item);
            }

            foreach ($demoRoute->brushlines as $item) {
                $item->setVisible([
                    'floor_id',
                    'polyline',
                ]);
                $toHide->add($item);
            }

            foreach ($demoRoute->paths as $item) {
                $item->load(['linkedawakenedobelisks']);
                $item->setVisible([
                    'floor_id',
                    'polyline',
                    'linkedawakenedobelisks',
                ]);
                $toHide->add($item);
            }

            foreach ($demoRoute->killZones as $item) {
                // Hidden by default to save data
                $item->makeVisible(['floor_id']);
                foreach ($item->spells as $spell) {
                    $spell->makeHidden([
                        'icon_name',
                        'icon_url',
                        'wowhead_url',
                    ]);
                }
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
                $item->makeHidden([
                    'id',
                    'dungeon_route_id',
                ]);
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
    private function saveFloor(Floor $floor, string $rootDirPath): void
    {
        $roundLatLngFn = static function (mixed $model) {
            /** @var HasLatLng $model */
            $model->lat = round($model->lat, 4);
            $model->lng = round($model->lng, 4);

            return $model;
        };

        $roundLatLngVerticesFn = static function (mixed $model) {
            /** @var HasVertices $model */
            $decodedLatLngs = $model->getDecodedLatLngs();
            foreach ($decodedLatLngs as $latLng) {
                $latLng->setLat(round($latLng->getLat(), 4));
                $latLng->setLng(round($latLng->getLng(), 4));
            }
            $model->vertices_json = json_encode($decodedLatLngs->toArray());

            return $model;
        };
        $roundLatLngPolyLinesFn = static function (mixed $model) use ($roundLatLngVerticesFn) {
            /** @var HasVertices $polyline */
            $polyline = $model->polyline;

            return $roundLatLngVerticesFn($polyline);
        };
//        $this->info(sprintf('-- Saving floor %s', __($floor->name)));
        // Only export NPC->id, no need to store the full npc in the enemy
        $enemies = $floor->enemiesForExport()->without([
            'npc',
            'type',
        ])->get()->makeVisible(['mdt_scale'])->values()
            ->each($roundLatLngFn);

        $enemyPacks   = $floor->enemyPacksForExport->values()->each($roundLatLngVerticesFn);
        $enemyPatrols = $floor->enemyPatrolsForExport->values()->makeVisible(['mdtPolyline'])->each($roundLatLngPolyLinesFn);
        /** @var \Illuminate\Database\Eloquent\Collection $dungeonFloorSwitchMarkers */
        $dungeonFloorSwitchMarkers = $floor->dungeonFloorSwitchMarkersForExport->values()->each($roundLatLngFn);
        // floorCouplingDirection is an attributed column which does not exist in the database; it exists in the DungeonData seeder
        $dungeonFloorSwitchMarkers
            ->makeHidden(['floorCouplingDirection'])
            ->map(static function (DungeonFloorSwitchMarker $dungeonFloorSwitchMarker) {
                $dungeonFloorSwitchMarker->direction = $dungeonFloorSwitchMarker->direction === '' ?
                    null : $dungeonFloorSwitchMarker->direction;

                return $dungeonFloorSwitchMarker;
            })
            ->each($roundLatLngFn);

        /** @var \Illuminate\Database\Eloquent\Collection $mapIcons */
        $mapIcons        = $floor->mapIconsForExport->values()->each($roundLatLngFn);
        $mountableAreas  = $floor->mountableAreasForExport->values()->each($roundLatLngVerticesFn);
        $floorUnions     = $floor->floorUnionsForExport()->without(['floorUnionAreas'])->get()->values()->each($roundLatLngFn);
        $floorUnionAreas = $floor->floorUnionAreasForExport->values()->each($roundLatLngVerticesFn);

        // Map icons can ALSO be added by users, thus we never know where this thing comes. As such, insert it
        // at the end of the table instead.
        $mapIcons->makeHidden([
            'id',
            'linked_awakened_obelisk_id',
        ]);

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
