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
use App\Models\Npc;
use App\Models\Npc\NpcEnemyForces;
use App\Models\NpcBolsteringWhitelist;
use App\Models\NpcSpell;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use App\Models\Spell;
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

    /** @var Collection|array */
    private Collection $importedModels;

    /** @var Collection|RelationMapping[] */
    private Collection $relationMapping;

    public function __construct()
    {
        $this->importedModels  = collect();
        $this->relationMapping = collect([
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
        ]);

        $this->resetImportedModels();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        // Just a base class
        $this->rollback();

        $this->command->info('Starting import of dungeon data for all dungeons');

        $this->importDungeonMapping();
        $this->flushModels();
        $this->importDungeonRoutes();
        $this->flushModels();

        Stopwatch::dumpAll();
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function importDungeonMapping(): void
    {
        $rootDir         = database_path(self::DUNGEON_DATA_DIR);
        $rootDirIterator = new FilesystemIterator($rootDir);

        // Parse the root files first
        foreach ($rootDirIterator as $rootDirChild) {
            /** @var $rootDirChild SplFileInfo */
            if ($rootDirChild->getType() === 'dir') {
                continue;
            }

            $this->parseRawFile($rootDir, $rootDirChild, 1);
        }

        $rootDirIterator->rewind();

        // For each expansion
        foreach ($rootDirIterator as $rootDirChild) {
            /** @var $rootDirChild SplFileInfo */
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
                /** @var $dungeonKeyDir SplFileInfo */
                $this->command->info('- Importing dungeon ' . basename($dungeonKeyDir));

                $floorDirIterator = new FilesystemIterator($dungeonKeyDir);
                // For each floor inside a dungeon dir
                foreach ($floorDirIterator as $floorDirFile) {
                    /** @var $floorDirFile SplFileInfo */
                    if ($floorDirFile->getType() !== 'dir') {
                        continue;
                    }
                    $this->command->info('-- Importing floor ' . basename($floorDirFile));

                    $importFileIterator = new FilesystemIterator($floorDirFile);
                    // For each file inside a floor
                    foreach ($importFileIterator as $importFile) {
                        // Floors first - parse dirs and only THEN files
                        $this->parseRawFile($rootDir, $importFile, 3);
                    }
                }

                $floorDirIterator->rewind();
                foreach ($floorDirIterator as $floorDirFile) {
                    /** @var $floorDirFile SplFileInfo */
                    if ($floorDirFile->getType() === 'dir') {
                        continue;
                    }

                    // Skip this for now - do it later
                    if (str_contains($floorDirFile, 'dungeonroutes')) {
                        continue;
                    }

                    // npcs, dungeon_routes
                    $this->parseRawFile($rootDir, $floorDirFile, 2);
                }
            }
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function importDungeonRoutes(): void
    {
        $rootDir         = database_path(self::DUNGEON_DATA_DIR);
        $rootDirIterator = new FilesystemIterator($rootDir);

        // For each expansion
        foreach ($rootDirIterator as $rootDirChild) {
            /** @var $rootDirChild SplFileInfo */
            if ($rootDirChild->getType() !== 'dir') {
                continue;
            }
            $expansionDirIterator = new FilesystemIterator($rootDirChild);

            // For each dungeon inside an expansion dir
            foreach ($expansionDirIterator as $dungeonKeyDir) {
                $this->command->info('- Importing dungeon ' . basename($dungeonKeyDir));

                $floorDirIterator = new FilesystemIterator($dungeonKeyDir);
                // For each floor inside a dungeon dir
                foreach ($floorDirIterator as $floorDirFile) {
                    /** @var $floorDirFile SplFileInfo */
                    if ($floorDirFile->getType() === 'dir') {
                        continue;
                    }

                    if (!str_contains($floorDirFile, 'dungeonroutes')) {
                        continue;
                    }

                    // npcs, dungeon_routes
                    $this->parseRawFile($rootDir, $floorDirFile, 2);
                }
            }
        }
    }

    /**
     * @return void
     */
    private function resetImportedModels(): void
    {
        // Init the place where we store all models so we can insert them all at once
        foreach ($this->relationMapping as $relationMapping) {
            $this->importedModels->put($relationMapping->getClass(), collect());
        }
    }

    /**
     * @return void
     */
    private function flushModels(): void
    {
        foreach ($this->importedModels as $class => $models) {
            /** @var $class \Eloquent */
            /** @var $models Collection */
            if ($models->isEmpty()) {
                continue;
            }

            $this->command->info(sprintf('- Saving %d %s', $models->count(), $class));

            $models->chunk(1000)->each(function (Collection $chunkedModels) use ($class) {
                $class::insert($chunkedModels->toArray());
            });
        }

        $this->resetImportedModels();
    }

    /**
     * @param string  $rootDir
     * @param string  $filePath
     * @param integer $depth
     *
     * @throws Exception
     */
    private function parseRawFile(string $rootDir, string $filePath, int $depth = 1): void
    {
        $prefix = str_repeat('-', $depth) . ' ';

        $fileName = basename($filePath);

        // Import file
        $this->command->info($prefix . 'Importing ' . $fileName);
        $found = false;
        foreach ($this->relationMapping as $mapping) {
            if ($mapping->getFileName() === $fileName) {
                $count = $this->loadModelsFromFile($filePath, $mapping);
                $this->command->info(sprintf(
                    $prefix . 'Imported %s (%s from %s)',
                    str_replace($rootDir, '', $fileName),
                    $count,
                    $fileName
                ));

                $found = true;
                break;
            }
        }

        // Let the user know if something wrong happened
        if (!$found) {
            $this->command->error($prefix . 'Unable to find table->model mapping for file ' . $filePath);
        }
    }

    /**
     * @param string          $filePath
     * @param RelationMapping $mapping
     *
     * @return int The amount of models loaded from the file
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
                $createdModel->save();
                $updatedModels++;

            } // If we should do some post processing, create & save it now so that we can do just that
            else if ($mapping->getPostSaveRelationParsers()->isNotEmpty()) {
                $createdModel = $mapping->getClass()::create($modelData);
                $updatedModels++;
            } // We don't need to do post processing, add it to the list to be saved
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
                    /** @var $attributeParser RelationParserInterface */
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
            $this->importedModels->put($mapping->getClass(),
                $this->importedModels->get($mapping->getClass())->merge($modelsToSave)
            );
        }

        // $this->command->info('OK _loadModelsFromFile ' . $filePath . ' ' . $modelClassName);
        return $updatedModels;
    }

    protected function rollback()
    {
        $this->command->warn('Truncating all relevant data...');

        // Can DEFINITELY NOT truncate DungeonRoute table here. That'd wipe the entire instance, not good.
        /** @var DungeonRoute[]|Collection $demoRoutes */
        $demoRoutes = DungeonRoute::with(['brushlines', 'paths', 'killZones', 'livesessions'])
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
        DB::table('map_icons')->whereNull('dungeon_route_id')->whereNull('team_id')->delete();
        // Delete polylines related to enemy patrols
        DB::table('polylines')->where('model_class', EnemyPatrol::class)->delete();
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            MappingVersion::class,
            MappingCommitLog::class,
            Spell::class,
            Npc::class,
            NpcBolsteringWhitelist::class,
            NpcEnemyForces::class,
            NpcSpell::class,
            Enemy::class,
            EnemyPack::class,
            EnemyPatrol::class,
            DungeonFloorSwitchMarker::class,
            MountableArea::class,
            FloorUnion::class,
            FloorUnionArea::class,
            DungeonSpeedrunRequiredNpc::class,
            // Do not truncate dungeons - we want to keep the active state of dungeons unique for each environment, if we truncate it it'd be reset
            // Dungeon::class,
            Floor::class,
            FloorCoupling::class,
        ];
    }
}
