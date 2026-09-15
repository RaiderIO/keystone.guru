<?php

namespace App\Console\Commands\Mapping;

use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Logic\Structs\LatLng;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\AffixGroup\AffixGroupCoupling;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyForcesCheckpoint;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Interfaces\HasLatLngInterface;
use App\Models\Interfaces\HasVerticesInterface;
use App\Models\MapIcon;
use App\Models\Mapping\MappingCommitLog;
use App\Models\Mapping\MappingVersion;
use App\Models\Season;
use App\Models\SeasonDungeon;
use App\Service\Mapping\MappingExportServiceInterface;
use App\Traits\SavesArrayToJsonFile;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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

    public function __construct(private readonly MappingExportServiceInterface $mappingExportService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): int
    {
        // Drop all caches for all models - otherwise it may produce some strange results
        $this->call('modelCache:clear');

        $dungeonDataDir = database_path('seeders/dungeondata/');
        $seasonDataDir  = database_path('seeders/seasondata/');

        $this->saveMappingVersions($dungeonDataDir);
        $this->saveMappingCommitLogs($dungeonDataDir);
        $this->saveDungeons($dungeonDataDir);
        $this->saveNpcs($dungeonDataDir);
        $this->saveSpells($dungeonDataDir);
        $this->saveSpellTuningChanges($dungeonDataDir);
        $this->saveDungeonData($dungeonDataDir);

        $this->saveAffixes($seasonDataDir);
        $this->saveAffixGroups($seasonDataDir);
        $this->saveSeasons($seasonDataDir);
        $this->saveSeasonDungeons($seasonDataDir);

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

            // Backed up as a sibling archive rather than folded into mapping.gz, so that the existing
            // archive's layout stays byte-compatible with backups taken before season data was exported.
            $seasonDataZippedFileName = 'seasondata.gz';
            $this->info(sprintf('Saving backup of season data to %s/%s', $targetDir, $seasonDataZippedFileName));
            $this->shell(sprintf('tar -zcf %s/%s -C %s .', $tmpZippedFilePath, $seasonDataZippedFileName, $seasonDataDir));
            $this->shell([
                sprintf('cp -R "%s/%s" "%s"', $tmpZippedFilePath, $seasonDataZippedFileName, $targetDir),
                sprintf('rm %s/%s', $tmpZippedFilePath, $seasonDataZippedFileName),
            ]);
        }

        return 0;
    }

    /**
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    private function saveDungeons(string $dungeonDataDir): void
    {
        // Save NPC data in the root of folder
        $this->info('Saving dungeons');

        // Save all dungeons
        $dungeons = Dungeon::without([
            'expansion',
            'gameVersion',
            'dungeonSpeedrunRequiredNpcs',
            'floors.floorUnions6',
        ])
            ->with([
                'floors.floorcouplings',
                'floors.dungeonSpeedrunRequiredNpcs.dungeonSpeedrunRequiredNpcNpcs',
                'dungeonSpeedrunDifficulties',
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
            ])
                ->makeHidden(['floor_count'])
                ->toArray(),
            $dungeonDataDir,
            'dungeons.json',
        );
    }

    /**
     * @param  string    $dungeonDataDir
     * @throws Exception
     */
    private function saveNpcs(string $dungeonDataDir): void
    {
        // Save NPC data in the root of folder
        $this->info('Saving NPCs');

        $this->saveDataToJsonFile($this->mappingExportService->serializeNpcs(), $dungeonDataDir, 'npcs.json');
    }

    /**
     * @param  string    $dungeonDataDir
     * @throws Exception
     */
    private function saveSpells(string $dungeonDataDir): void
    {
        // Save all spells
        $this->info('Saving Spells');

        $this->saveDataToJsonFile($this->mappingExportService->serializeSpells(), $dungeonDataDir, 'spells.json');
    }

    /**
     * @param  string    $dungeonDataDir
     * @throws Exception
     */
    private function saveSpellTuningChanges(string $dungeonDataDir): void
    {
        // Save the build-over-build spell tuning changes computed by spell:difftuning
        $this->info('Saving Spell tuning changes');

        $this->saveDataToJsonFile($this->mappingExportService->serializeSpellTuningChanges(), $dungeonDataDir, 'spell_tuning_changes.json');
    }

    /**
     * Attributes are listed explicitly rather than relying on toArray(), so that the file format is a
     * deliberate contract instead of a by-product of the model's $hidden/$appends/$with configuration.
     *
     * @throws Exception
     */
    private function saveAffixes(string $seasonDataDir): void
    {
        $this->info('Saving affixes');

        $affixes = Affix::query()
            ->orderBy('id')
            ->get()
            ->map(static fn(Affix $affix): array => [
                'id'          => $affix->id,
                'key'         => $affix->key,
                'affix_id'    => $affix->affix_id,
                'name'        => $affix->name,
                'description' => $affix->description,
            ]);

        $this->saveDataToJsonFile($affixes->all(), $seasonDataDir, 'affixes.json');
    }

    /**
     * Affix group couplings are nested inside their affix group so that a rotation change reads as
     * "this week's third affix changed" in review, rather than as opaque coupling churn.
     *
     * The couplings deliberately carry no id of their own: nothing references affix_group_couplings.id,
     * it only encodes the display order (see the explicit orderBy in AffixGroupBase::affixes()). Array
     * order therefore *is* the contract - it becomes insertion order, which becomes id order.
     *
     * @throws Exception
     */
    private function saveAffixGroups(string $seasonDataDir): void
    {
        $this->info('Saving affix groups');

        $affixGroups = AffixGroup::without(['affixes'])
            // affixGroupCouplings() is an unordered HasMany - without this the nested order would be
            // whatever the database happens to return, making the export non-deterministic.
            ->with(['affixGroupCouplings' => static fn(Relation $query) => $query->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(static fn(AffixGroup $affixGroup): array => [
                'id'             => $affixGroup->id,
                'season_id'      => $affixGroup->season_id,
                'expansion_id'   => $affixGroup->expansion_id,
                'seasonal_index' => $affixGroup->seasonal_index,
                'confirmed'      => (bool)$affixGroup->confirmed,
                'affixes'        => $affixGroup->affixGroupCouplings
                    ->map(static fn(AffixGroupCoupling $affixGroupCoupling): array => [
                        'affix_id'  => $affixGroupCoupling->affix_id,
                        'key_level' => $affixGroupCoupling->key_level,
                    ])->all(),
            ]);

        $this->saveDataToJsonFile($affixGroups->all(), $seasonDataDir, 'affix_groups.json');
    }

    /**
     * @throws Exception
     */
    private function saveSeasons(string $seasonDataDir): void
    {
        $this->info('Saving seasons');

        $seasons = Season::query()
            ->orderBy('id')
            ->get()
            ->map(static fn(Season $season): array => [
                'id'                => $season->id,
                'expansion_id'      => $season->expansion_id,
                'seasonal_affix_id' => $season->seasonal_affix_id,
                'index'             => $season->index,
                // Formatted rather than left as the datetime cast: the seeder inserts through the query
                // builder, which would hand MySQL an ISO-8601 string that a `datetime` column rejects.
                'start'                   => $season->start->toDateTimeString(),
                'active'                  => $season->active,
                'presets'                 => $season->presets,
                'affix_group_count'       => $season->affix_group_count,
                'start_affix_group_index' => $season->start_affix_group_index,
                'key_level_min'           => $season->key_level_min,
                'key_level_max'           => $season->key_level_max,
                'item_level_min'          => $season->item_level_min,
                'item_level_max'          => $season->item_level_max,
            ]);

        $this->saveDataToJsonFile($seasons->all(), $seasonDataDir, 'seasons.json');
    }

    /**
     * @throws Exception
     */
    private function saveSeasonDungeons(string $seasonDataDir): void
    {
        $this->info('Saving season dungeons');

        $seasonDungeons = SeasonDungeon::without(['season', 'dungeon'])
            ->orderBy('id')
            ->get()
            ->map(static fn(SeasonDungeon $seasonDungeon): array => [
                'id'         => $seasonDungeon->id,
                'season_id'  => $seasonDungeon->season_id,
                'dungeon_id' => $seasonDungeon->dungeon_id,
            ]);

        $this->saveDataToJsonFile($seasonDungeons->all(), $seasonDataDir, 'season_dungeons.json');
    }

    /**
     * @param  string    $dungeonDataDir
     * @throws Exception
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
                'enemyForcesCheckpointsForExport',
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
     * @throws Exception
     */
    private function saveDungeonDungeonRoutes(Dungeon $dungeon, string $rootDirPath): void
    {
        // Demo routes, load it in a specific way to make it easier to import it back in again
        foreach ($dungeon->dungeonRoutesForExport as $demoRoute) {
            /** @var $demoRoute DungeonRoute */
            $demoRoute->unsetRelations();
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
                'killZones.enemies:id',
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
     * @throws Exception
     */
    private function saveFloor(Floor $floor, string $rootDirPath): void
    {
        $roundLatLngFn = static function (Model&HasLatLngInterface $model) {
            $latLng = $model->getLatLng();
            $model->setLatLng(new LatLng(
                round($latLng->getLat(), 4),
                round($latLng->getLng(), 4),
                $latLng->getFloor(),
            ));

            return $model;
        };

        $roundLatLngVerticesFn = static function (Model&HasVerticesInterface $model) {
            $decodedLatLngs = $model->getDecodedLatLngs();
            foreach ($decodedLatLngs as $latLng) {
                $latLng->setLat(round($latLng->getLat(), 4));
                $latLng->setLng(round($latLng->getLng(), 4));
            }
            $model->setAttribute('vertices_json', json_encode($decodedLatLngs->toArray()));

            return $model;
        };
        $roundLatLngPolyLinesFn = static function (mixed $model) use ($roundLatLngVerticesFn) {
            /** @var Model&HasVerticesInterface $polyline */
            $polyline = $model->polyline;

            return $roundLatLngVerticesFn($polyline);
        };
//        $this->info(sprintf('-- Saving floor %s', __($floor->name)));
        // Only export NPC->id, no need to store the full npc in the enemy
        /** @var EloquentCollection<int, Model&HasLatLngInterface> $enemiesCollection */
        $enemiesCollection = $floor->enemiesForExport()
            ->without([
                'npc',
                'type',
            ])
            ->get()
            ->each(static fn(Enemy $enemy) => $enemy->setRelation('floor', $floor))
            ->makeVisible(['mdt_scale'])
            ->values();
        $enemies = $enemiesCollection->each($roundLatLngFn);

        $enemyPacks   = $floor->enemyPacksForExport->values()->each($roundLatLngVerticesFn);
        $enemyPatrols = $floor->enemyPatrolsForExport->makeVisible(['mdtPolyline'])->values()->each($roundLatLngPolyLinesFn);
        /** @var EloquentCollection<int, DungeonFloorSwitchMarker> $dungeonFloorSwitchMarkers */
        $dungeonFloorSwitchMarkers = $floor->dungeonFloorSwitchMarkersForExport
            ->each(static fn(DungeonFloorSwitchMarker $item) => $item->setRelation('floor', $floor))
            ->values()
            ->each($roundLatLngFn);
        // floorCouplingDirection is an attributed column which does not exist in the database; it exists in the DungeonData seeder
        /** @var EloquentCollection<int, DungeonFloorSwitchMarker&HasLatLngInterface> $mappedSwitchMarkers */
        $mappedSwitchMarkers = $dungeonFloorSwitchMarkers
            ->makeHidden(['floorCouplingDirection'])
            ->map(static function (DungeonFloorSwitchMarker $dungeonFloorSwitchMarker) {
                $dungeonFloorSwitchMarker->direction = $dungeonFloorSwitchMarker->direction === '' ?
                    null : $dungeonFloorSwitchMarker->direction;

                return $dungeonFloorSwitchMarker;
            });
        $mappedSwitchMarkers->each($roundLatLngFn);

        /** @var EloquentCollection<int, Model&HasLatLngInterface> $mapIcons */
        $mapIcons = $floor->mapIconsForExport
            ->each(static fn(MapIcon $item) => $item->setRelation('floor', $floor))
            ->values()
            ->each($roundLatLngFn);
        $mountableAreas = $floor->mountableAreasForExport->values()->each($roundLatLngVerticesFn);
        /** @var EloquentCollection<int, Model&HasLatLngInterface> $enemyForcesCheckpoints */
        $enemyForcesCheckpoints = $floor->enemyForcesCheckpointsForExport
            ->each(static fn(EnemyForcesCheckpoint $item) => $item->setRelation('floor', $floor))
            ->values()
            ->each($roundLatLngFn);
        /** @var EloquentCollection<int, Model&HasLatLngInterface> $floorUnionsCollection */
        $floorUnionsCollection = $floor->floorUnionsForExport()->without(['floorUnionAreas'])->get()
            ->each(static fn(FloorUnion $item) => $item->setRelation('floor', $floor))
            ->values();
        $floorUnions     = $floorUnionsCollection->each($roundLatLngFn);
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
        $result['enemy_forces_checkpoints']     = $enemyForcesCheckpoints;
        $result['floor_unions']                 = $floorUnions;
        $result['floor_union_areas']            = $floorUnionAreas;

        foreach ($result as $category => $categoryData) {
            // Save enemies, packs, patrols, markers on a per-floor basis
//            if ($categoryData->count() > 0) {
//                $this->info(sprintf('--- Saving %s %s', $categoryData->count(), $category));
//            }

            $this->saveDataToJsonFile($categoryData->toArray(), sprintf('%s/%s', $rootDirPath, $floor->index), sprintf('%s.json', $category));
        }
    }
}
