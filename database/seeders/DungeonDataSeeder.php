<?php

namespace Database\Seeders;

use App\Logic\Utils\Stopwatch;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingCommitLog;
use App\Models\Mapping\MappingVersion;
use App\Models\MountableArea;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcBolsteringWhitelist;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcHealth;
use App\Models\Speedrun\DungeonSpeedrunDifficulty;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpcNpc;
use App\Models\Spell\Spell;
use App\SeederHelpers\RelationImport\Mapping\DungeonFloorSwitchMarkerRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\DungeonRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\DungeonRouteRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\EnemyPackRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\EnemyPatrolRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\EnemyRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\FloorUnionAreaRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\FloorUnionRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\MapIconRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\MappingCommitLogRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\MappingVersionRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\MountableAreaRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\NpcRelationMapping;
use App\SeederHelpers\RelationImport\Mapping\RelationMapping;
use App\SeederHelpers\RelationImport\Mapping\SpellRelationMapping;
use App\SeederHelpers\RelationImport\Parsers\Relation\RelationParserInterface;
use Exception;
use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SplFileInfo;

class DungeonDataSeeder extends Seeder implements TableSeederInterface
{
    private const DUNGEON_DATA_DIR = 'seeders/dungeondata/';

    /** @var array<string, Collection<int, array<string, mixed>>> */
    private array $importedModels;

    /** @var array<int, RelationMapping> */
    private array $relationMapping;

    public function __construct()
    {
        $this->importedModels  = [];
        $this->relationMapping = [
            // Loose files
            new MappingVersionRelationMapping(),
            new MappingCommitLogRelationMapping(),
            new DungeonRelationMapping(),
            new NpcRelationMapping(),
            new DungeonRouteRelationMapping(),
            new SpellRelationMapping(),

            // Files inside floor folder
            new EnemyRelationMapping(),

            new EnemyPackRelationMapping(),
            new EnemyPatrolRelationMapping(),
            new DungeonFloorSwitchMarkerRelationMapping(),
            new MapIconRelationMapping(),
            new MountableAreaRelationMapping(),
            new FloorUnionRelationMapping(),
            new FloorUnionAreaRelationMapping(),
        ];

        $this->resetImportedModels();
    }

    /**
     * Run the database seeds.
     *
     * @throws Exception
     */
    public function run(): void
    {
        // Just a base class
        $this->rollback();

        $this->importDungeonMapping();
        $this->flushModels();
        $this->importDungeonRoutes();
        $this->flushModels();
        $this->preserveColumns();

        Stopwatch::dumpAll();
    }

    /**
     * @throws Exception
     */
    private function importDungeonMapping(): void
    {
        $rootDir         = database_path(self::DUNGEON_DATA_DIR);
        $rootDirIterator = new FilesystemIterator($rootDir);

        // Parse the root files first
        foreach ($rootDirIterator as $rootDirChild) {
            /** @var SplFileInfo $rootDirChild */
            if ($rootDirChild->getType() === 'dir') {
                continue;
            }

            $result = $this->parseRawFile($rootDirChild);
            if ($result !== null) {
                $this->command->info(sprintf('- Imported %d %s', $result['count'], $this->humanizeFileName($result['fileName'])));
            }
        }

        $rootDirIterator->rewind();

        // For each expansion
        foreach ($rootDirIterator as $rootDirChild) {
            /** @var SplFileInfo $rootDirChild */
            if ($rootDirChild->getType() !== 'dir') {
                continue;
            }

            $rootDirChildBaseName = basename($rootDirChild);
            // Only folders which have the correct shortname
            if (Expansion::where('shortname', $rootDirChildBaseName)->first() === null) {
                $this->command->warn(sprintf('- Unable to find expansion %s', $rootDirChildBaseName));

                continue;
            }

            $this->command->info('Expansion ' . $rootDirChildBaseName);
            $expansionDirIterator = new FilesystemIterator($rootDirChild);

            // For each dungeon inside an expansion dir
            foreach ($expansionDirIterator as $dungeonKeyDir) {
                /** @var SplFileInfo $dungeonKeyDir */
                /** @var array<string, int> $counts */
                $counts = [];

                $floorDirIterator = new FilesystemIterator($dungeonKeyDir);
                // For each floor inside a dungeon dir
                foreach ($floorDirIterator as $floorDirFile) {
                    /** @var SplFileInfo $floorDirFile */
                    if ($floorDirFile->getType() !== 'dir') {
                        continue;
                    }

                    $importFileIterator = new FilesystemIterator($floorDirFile);
                    // For each file inside a floor
                    foreach ($importFileIterator as $importFile) {
                        // Floors first - parse dirs and only THEN files
                        $this->accumulateImportCount($counts, $this->parseRawFile($importFile));
                    }
                }

                $floorDirIterator->rewind();
                foreach ($floorDirIterator as $floorDirFile) {
                    /** @var SplFileInfo $floorDirFile */
                    if ($floorDirFile->getType() === 'dir') {
                        continue;
                    }

                    // Skip this for now - do it later
                    if (str_contains($floorDirFile, 'dungeonroutes')) {
                        continue;
                    }

                    // npcs, dungeon_routes
                    $this->accumulateImportCount($counts, $this->parseRawFile($floorDirFile));
                }

                $this->command->info(sprintf('- %s: %s', basename($dungeonKeyDir), $this->buildImportSummary($counts)));
            }
        }
    }

    /**
     * @throws Exception
     */
    private function importDungeonRoutes(): void
    {
        $rootDir         = database_path(self::DUNGEON_DATA_DIR);
        $rootDirIterator = new FilesystemIterator($rootDir);

        // For each expansion
        foreach ($rootDirIterator as $rootDirChild) {
            /** @var SplFileInfo $rootDirChild */
            if ($rootDirChild->getType() !== 'dir') {
                continue;
            }
            $expansionDirIterator = new FilesystemIterator($rootDirChild);

            // For each dungeon inside an expansion dir
            foreach ($expansionDirIterator as $dungeonKeyDir) {
                $count = 0;

                $floorDirIterator = new FilesystemIterator($dungeonKeyDir);
                // For each floor inside a dungeon dir
                foreach ($floorDirIterator as $floorDirFile) {
                    /** @var SplFileInfo $floorDirFile */
                    if ($floorDirFile->getType() === 'dir') {
                        continue;
                    }

                    if (!str_contains($floorDirFile, 'dungeonroutes')) {
                        continue;
                    }

                    // npcs, dungeon_routes
                    $count += $this->parseRawFile($floorDirFile)['count'] ?? 0;
                }

                if ($count > 0) {
                    $this->command->info(sprintf('- %s: %d dungeon routes', basename($dungeonKeyDir), $count));
                }
            }
        }
    }

    private function resetImportedModels(): void
    {
        // Init the place where we store all models so we can insert them all at once
        foreach ($this->relationMapping as $relationMapping) {
            $this->importedModels[$relationMapping->getClass()] = collect();
        }
    }

    /**
     * For each mapping that declares preserved columns, copy those column values from the live table
     * into the temp table. This runs after all models have been flushed into temp tables but before
     * DatabaseSeeder swaps them in, so that combat-log-derived data survives a re-seed.
     * New rows (not yet in the live table) keep the defaults from the JSON file.
     */
    private function preserveColumns(): void
    {
        foreach ($this->relationMapping as $mapping) {
            $preservedColumns = $mapping->getPreservedColumns();
            if (empty($preservedColumns)) {
                continue;
            }

            $instance  = new ($mapping->getClass())();
            $liveTable = $instance->getTable();
            $tempTable = DatabaseSeeder::getTempTableName($mapping->getClass());
            /** @var Collection<int, string> $columns */
            $columns    = collect($preservedColumns);
            $setClauses = $columns
                ->map(fn(string $col) => sprintf('t.%s = orig.%s', $col, $col))
                ->implode(', ');

            /** @noinspection SqlWithoutWhere */
            DB::statement(sprintf(
                'UPDATE %s t INNER JOIN %s orig ON orig.id = t.id SET %s',
                $tempTable,
                $liveTable,
                $setClauses,
            ));
        }
    }

    private function flushModels(): void
    {
        foreach ($this->importedModels as $class => $models) {
            /** @var class-string<Model> $class */
            /** @var Collection<int, array<string, mixed>> $models */
            if ($models->isEmpty()) {
                continue;
            }

            $this->command->info(sprintf('- Saving %d %s', $models->count(), $class));

            $models->chunk(1000)->each(function (Collection $chunkedModels) use ($class) {
                $class::from(DatabaseSeeder::getTempTableName($class))->insert($chunkedModels->toArray());
            });
        }

        $this->resetImportedModels();
    }

    /**
     * @return array{fileName: string, count: int}|null Null when no mapping matched the file.
     *
     * @throws Exception
     */
    private function parseRawFile(string $filePath): ?array
    {
        $fileName = basename($filePath);

        foreach ($this->relationMapping as $mapping) {
            if ($mapping->getFileName() === $fileName) {
                return ['fileName' => $fileName, 'count' => $this->loadModelsFromFile($filePath, $mapping)];
            }
        }

        $this->command->error('Unable to find table->model mapping for file ' . $filePath);

        return null;
    }

    /**
     * Turns "enemy_packs.json" into "enemy packs" for human-readable output.
     */
    private function humanizeFileName(string $fileName): string
    {
        return str_replace('_', ' ', basename($fileName, '.json'));
    }

    /**
     * Builds "4 map icons, 192 enemies, 51 packs" from a fileName => count map,
     * omitting zero counts and ordering parts by the configured relation mapping order.
     *
     * @param array<string, int> $counts
     */
    private function buildImportSummary(array $counts): string
    {
        $parts = [];
        foreach ($this->relationMapping as $mapping) {
            $count = $counts[$mapping->getFileName()] ?? 0;
            if ($count > 0) {
                $parts[] = sprintf('%d %s', $count, $this->humanizeFileName($mapping->getFileName()));
            }
        }

        return empty($parts) ? 'nothing imported' : implode(', ', $parts);
    }

    /**
     * @param array<string, int>                       $counts
     * @param array{fileName: string, count: int}|null $result
     */
    private function accumulateImportCount(array &$counts, ?array $result): void
    {
        if ($result === null) {
            return;
        }

        $counts[$result['fileName']] = ($counts[$result['fileName']] ?? 0) + $result['count'];
    }

    /**
     * @return int The amount of models loaded from the file
     *
     * @throws Exception
     */
    private function loadModelsFromFile(string $filePath, RelationMapping $mapping): int
    {
        // Load contents
        $modelJson = file_get_contents($filePath);
        // Convert to models
        $modelsData = json_decode($modelJson, true);

        // Pre-fetch all valid columns to make the below loop a bit faster
        $modelColumns = Schema::getColumnListing($mapping->getClass()::newModelInstance()->getTable());

        // In case there's no post-save relation parsers we can mass-save instead to save time
        $modelsToSave = collect();

        $updatedModels = 0;

        // Do some php fuckery to make this a bit cleaner
        foreach ($modelsData as $modelData) {
            $shouldParseModel = true;

            // First, check if we may even insert this model (for example if the mapping version is not new enough so we shouldn't re-insert the model)
            foreach ($mapping->getConditionals() as $conditional) {
                if (!($shouldParseModel = $conditional->shouldParseModel($mapping, $modelData))) {
                    break;
                }
            }

            // Ok, we found a reason not to parse this model. Continue to the next model
            if (!$shouldParseModel) {
                continue;
            }

            $unsetRelations = [];
            // We're editing $modelData inside the loop - don't convert it to foreach nor move the count() outside the loop
            for ($i = 0; $i < count($modelData); $i++) {
                $keys  = array_keys($modelData);
                $key   = $keys[$i];
                $value = $modelData[$key];

                // Parse individual attributes of the root object
                foreach ($mapping->getAttributeParsers() as $attributeParser) {
                    if (!is_array($value) &&
                        $attributeParser->canParseModel($mapping->getClass())) {
                        $modelData = $attributeParser->parseAttribute($mapping->getClass(), $modelData, $key, $value);
                    }
                }

                // Parse the relations of the root object
                foreach ($mapping->getPreSaveRelationParsers() as $relationParser) {
                    // Relations are always arrays, so exclude those that are not, then verify if the parser can handle this, then if it can, parse it
                    if (is_array($value) &&
                        $relationParser->canParseModel($mapping->getClass()) &&
                        $relationParser->canParseRelation($key, $value)) {
                        $modelData = $relationParser->parseRelation($mapping->getClass(), $modelData, $key, $value);
                    }
                }

                // The column may not be set due to objects appearing in this array that need to be de-normalized
                if (!in_array($key, $modelColumns)) {
                    // Keep track of all relations we removed so we can parse them again after saving the model
                    $unsetRelations[$key] = $value;
                    unset($modelData[$key]);
                    $i--;
                }
            }

            // $this->command->info("Creating model " . json_encode($modelData));
            // Create and save a new instance to the database

            /** @var Model $createdModel */
            // Check if we need to update this model instead of saving it
            if (isset($modelData['id']) && $mapping->isPersistent()) {
                // Load first
                $createdModel = $mapping->getClass()::findOrNew($modelData['id']);
                // Apply, then save
                $createdModel->setRawAttributes($modelData);
                $createdModel->setTable(DatabaseSeeder::getTempTableName($mapping->getClass()))->save();
                $updatedModels++;
            } // If we should do some post-processing, create & save it now so that we can do just that
            elseif ($mapping->getPostSaveRelationParsers()->isNotEmpty()) {
                /** @var class-string<Model> $mappingClass */
                $mappingClass = $mapping->getClass();
                $createdModel = $mappingClass::from(DatabaseSeeder::getTempTableName($mappingClass))->create($modelData);
                $updatedModels++;
            } // We don't need to do post-processing, add it to the list to be saved
            else {
                $modelsToSave->push($modelData);
                $updatedModels++;

                continue;
            }

            // If we have models to mass-save later, we should not do post-processing since it's incompatible
            if ($modelsToSave->isNotEmpty()) {
                continue;
            }

            $modelData['id'] = $createdModel->id;

            // Merge the unset relations with the model again so we can parse the model again
            $modelData = $modelData + $unsetRelations;

            foreach ($mapping->getPostSaveRelationParsers() as $attributeParser) {
                foreach ($modelData as $key => $value) {
                    /** @var RelationParserInterface $attributeParser */
                    // Relations are always arrays, so exclude those that are not, then verify if the parser can handle this, then if it can, parse it
                    if (is_array($value) &&
                        $attributeParser->canParseModel($mapping->getClass()) &&
                        $attributeParser->canParseRelation($key, $value)) {
                        // Ignore return value, use preModelSaveAttributeParser if you want the parser to have effect on the
                        // model that's about to be saved. It's already saved at this point
                        $attributeParser->parseRelation($mapping->getClass(), $modelData, $key, $value);
                    }
                }
            }
        }

        // Bulk save the models that did not need any post-attribute parsing
        if ($modelsToSave->isNotEmpty()) {
            /** @var Collection<int, array<string, mixed>> $importedModels */
            $importedModels                             = $this->importedModels[$mapping->getClass()] ?? collect();
            $this->importedModels[$mapping->getClass()] = $importedModels->merge($modelsToSave);
        }

        // $this->command->info('OK _loadModelsFromFile ' . $filePath . ' ' . $modelClassName);
        return $updatedModels;
    }

    protected function rollback(): void
    {
        $this->command->warn('Truncating all relevant data...');

        // Can DEFINITELY NOT truncate DungeonRoute table here. That'd wipe the entire instance, not good.
        /** @var Collection<int, DungeonRoute> $demoRoutes */
        $demoRoutes = DungeonRoute::with([
            'brushlines',
            'paths',
            'killZones',
            'livesessions',
        ])
            ->where('demo', true)
            ->get();

        // Delete each found route that was a demo (controlled by me only)
        // This will remove all killzones, brushlines, paths etc related to the route.
        foreach ($demoRoutes as $demoRoute) {
            try {
                $demoRoute->delete();
            } catch (Exception $ex) {
                $this->command->error(sprintf('%s: Exception deleting demo dungeonroute', $ex->getMessage()));
            }
        }
        // Delete all map icons that are always there
        DB::table('map_icons')->whereNotNull('mapping_version_id')->delete();
        // Delete polylines related to enemy patrols
        DB::table('polylines')->where('model_class', EnemyPatrol::class)->delete();
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            MappingVersion::class,
            MappingCommitLog::class,
            Spell::class,
            // SpellDungeon, NpcCharacteristic and NpcSpell are combat-log-derived behavior and are
            // intentionally omitted: they are not exported to the seeders, so their live tables must
            // survive a re-seed untouched instead of being rebuilt (and wiped) from the JSON files.
            Npc::class,
            NpcBolsteringWhitelist::class,
            NpcEnemyForces::class,
            NpcDungeon::class,
            NpcHealth::class,
            Enemy::class,
            EnemyPack::class,
            EnemyPatrol::class,
            DungeonFloorSwitchMarker::class,
            MountableArea::class,
            FloorUnion::class,
            FloorUnionArea::class,
            DungeonSpeedrunRequiredNpc::class,
            DungeonSpeedrunRequiredNpcNpc::class,
            DungeonSpeedrunDifficulty::class,
            // Do not truncate dungeons - we want to keep the active state of dungeons unique for each environment, if we truncate it it'd be reset
            // Dungeon::class,
            Floor::class,
            FloorCoupling::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
