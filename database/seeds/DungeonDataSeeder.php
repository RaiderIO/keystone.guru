<?php

use App\Models\DungeonRoute;
use App\Models\Expansion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DungeonDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Exception
     */
    public function run()
    {
        // Just a base class
        $this->_rollback();

        $this->command->info('Starting import of dungeon data for all dungeons');

        $nameMapping = [
            // Loose files
            'dungeons'                     => 'App\Models\Dungeon',
            'npcs'                         => 'App\Models\Npc',
            'dungeonroutes'                => 'App\Models\DungeonRoute',

            // Files inside floor folder
            'enemies'                      => 'App\Models\Enemy',
            'enemy_packs'                  => 'App\Models\EnemyPack',
            'enemy_patrols'                => 'App\Models\EnemyPatrol',
            'dungeon_floor_switch_markers' => 'App\Models\DungeonFloorSwitchMarker',
            'map_icons'                    => 'App\Models\MapIcon'
        ];

        $rootDir = database_path('/seeds/dungeondata/');
        $rootDirIterator = new FilesystemIterator($rootDir);

        // For each expansion
        foreach ($rootDirIterator as $rootDirChild) {
            $rootDirChildBaseName = basename($rootDirChild);

            // Only folders which have the correct shortname
            if (Expansion::where('shortname', $rootDirChildBaseName)->first() !== null) {
                $this->command->info('Expansion ' . $rootDirChildBaseName);
                $expansionDirIterator = new FilesystemIterator($rootDirChild);

                // For each dungeon inside an expansion dir
                foreach ($expansionDirIterator as $dungeonKeyDir) {
                    $this->command->info('- Importing dungeon ' . basename($dungeonKeyDir));

                    $floorDirIterator = new FilesystemIterator($dungeonKeyDir);
                    // For each floor inside a dungeon dir
                    foreach ($floorDirIterator as $floorDirFile) {
                        // Parse loose files
                        if (!is_dir($floorDirFile)) {
                            // npcs, dungeon_routes
                            $this->_parseRawFile($rootDir, $floorDirFile, $nameMapping, 2);
                        } // Parse floor dir
                        else {
                            $this->command->info('-- Importing floor ' . basename($floorDirFile));

                            $importFileIterator = new FilesystemIterator($floorDirFile);
                            // For each file inside a floor
                            foreach ($importFileIterator as $importFile) {
                                $this->_parseRawFile($rootDir, $importFile, $nameMapping, 3);
                            }
                        }
                    }
                }
            } // It's a 'global' file, parse it
            else if (strpos($rootDirChild, '.json') === strlen($rootDirChild) - 5) { // 5 for length of .json
                $this->_parseRawFile($rootDir, $rootDirChild, $nameMapping, 1);
            }
        }
    }

    /**
     * @param $rootDir string
     * @param $file string
     * @param $nameMapping array
     * @param $depth integer
     * @throws Exception
     */
    private function _parseRawFile($rootDir, $file, $nameMapping, $depth = 1)
    {
        $prefix = str_repeat('-', $depth) . ' ';

        $tableName = basename($file, '.json');

        // Import file
        $this->command->info($prefix . 'Importing ' . $tableName);
        // Get contents
        if (!array_key_exists($tableName, $nameMapping)) {
            $this->command->error($prefix . 'Unable to find table->model mapping for file ' . $file);
        } else {
            $count = $this->_loadModelsFromFile($file, $nameMapping[$tableName]);
            $this->command->info(sprintf($prefix . 'Imported %s (%s into %s)', str_replace($rootDir, '', $file), $count, $tableName));
        }
    }

    /**
     * @param $filePath string
     * @param $modelClassName Model
     * @param $update boolean
     * @return int The amount of models loaded from the file
     * @throws Exception
     */
    private function _loadModelsFromFile($filePath, $modelClassName, $update = false)
    {
        // $this->command->info('>> _loadModelsFromFile ' . $filePath . ' ' . $modelClassName);
        // Load contents
        $modelJson = file_get_contents($filePath);
        // Convert to models
        $modelsData = json_decode($modelJson, true);

        // Pre-fetch all valid columns to make the below loop a bit faster
        $modelColumns = Schema::getColumnListing($modelClassName::newModelInstance()->getTable());

        $preModelSaveAttributeParsers = [
            // Generic
            new NestedModelRelationParser(),

            // Enemy Patrols, Paths and Brushlines
            new EnemyPatrolPolylineRelationParser(),

            // Npc
            new NpcNpcBolsteringWhitelistRelationParser(),
        ];

        // Parse these attributes AFTER the model has been inserted into the database (so we know its ID)
        $postModelSaveAttributeParsers = [
            // Dungeon route
            new DungeonRoutePlayerSpecializationRelationParser(),
            new DungeonRoutePlayerRaceRelationParser(),
            new DungeonRoutePlayerClassRelationParser(),

            new DungeonRouteAttributesRelationParser(),

            new DungeonRouteAffixGroupRelationParser(),

            new DungeonRouteBrushlinesRelationParser(),
            new DungeonRoutePathsRelationParser(),

            new DungeonRouteKillZoneRelationParser(),

            new DungeonRouteEnemyRaidMarkersRelationParser(),
            new DungeonRoutePridefulEnemiesRelationParser(),

            new DungeonRouteMapIconsRelationParser()
        ];

        // Do some php fuckery to make this a bit cleaner
        foreach ($modelsData as $modelData) {

            $unsetRelations = [];

            for ($i = 0; $i < count($modelData); $i++) {
                $keys = array_keys($modelData);
                $key = $keys[$i];
                $value = $modelData[$key];

                // $this->command->info(json_encode($key) . ' ' . json_encode($value));

                foreach ($preModelSaveAttributeParsers as $attributeParser) {
                    /** @var $attributeParser RelationParser */
                    // Relations are always arrays, so exclude those that are not, then verify if the parser can handle this, then if it can, parse it
                    if (is_array($value) &&
                        $attributeParser->canParseModel($modelClassName) &&
                        $attributeParser->canParseRelation($key, $value)) {

                        $modelData = $attributeParser->parseRelation($modelClassName, $modelData, $key, $value);
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
            if (isset($modelData['id'])) {
                // Load first
                $createdModel = $modelClassName::findOrNew($modelData['id']);
                // Apply, then save
                $createdModel->setRawAttributes($modelData);
                $createdModel->save();
            } else {
                $createdModel = $modelClassName::create($modelData);
            }
            $modelData['id'] = $createdModel->id;

            // Merge the unset relations with the model again so we can parse the model again
            $modelData = $modelData + $unsetRelations;

            foreach ($modelData as $key => $value) {
                foreach ($postModelSaveAttributeParsers as $attributeParser) {
                    /** @var $attributeParser RelationParser */
                    // Relations are always arrays, so exclude those that are not, then verify if the parser can handle this, then if it can, parse it
                    if (is_array($value) &&
                        $attributeParser->canParseModel($modelClassName) &&
                        $attributeParser->canParseRelation($key, $value)) {

                        // Ignore return value, use preModelSaveAttributeParser if you want the parser to have effect on the
                        // model that's about to be saved. It's already saved at this point
                        $attributeParser->parseRelation($modelClassName, $modelData, $key, $value);
                    }
                }
            }
        }

        // $this->command->info('OK _loadModelsFromFile ' . $filePath . ' ' . $modelClassName);
        return count($modelsData);
    }

    protected function _rollback()
    {
        $this->command->warn('Truncating all relevant data...');
        DB::table('npcs')->truncate();
        DB::table('npc_bolstering_whitelists')->truncate();
        DB::table('enemies')->truncate();
        DB::table('enemy_packs')->truncate();
        DB::table('enemy_patrols')->truncate();
        DB::table('dungeon_floor_switch_markers')->truncate();
        // Delete all map icons that are always there
        DB::table('map_icons')->where('dungeon_route_id', -1)->delete();
        // Delete polylines related to enemy patrols
        DB::table('polylines')->where('model_class', 'App\Models\EnemyPatrol')->delete();

        // Can DEFINITELY NOT truncate DungeonRoute table here. That'd wipe the entire instance, not good.
        $demoRoutes = DungeonRoute::all()->where('demo', true);

        // Delete each found route that was a demo (controlled by me only)
        // This will remove all killzones, brushlines, paths etc related to the route.
        foreach ($demoRoutes as $demoRoute) {
            /** @var $demoRoute DungeonRoute */
            try {
                /** @var $demoRoute Model */
                $demoRoute->delete();
            } catch (Exception $ex) {
                $this->command->error('Exception deleting demo dungeonroute');
            }
        }
    }
}
