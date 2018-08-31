<?php

use Illuminate\Database\Seeder;

class DungeonDataSeeder extends Seeder
{
    /**
     * @var \App\Models\Dungeon The current dungeon.
     */
    private $_dungeon;

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
            'npcs' => '\App\Models\Npc',
            'dungeonroutes' => '\App\Models\DungeonRoute',

            // Files inside floor folder
            'enemies' => '\App\Models\Enemy',
            'enemy_packs' => '\App\Models\EnemyPack',
            'enemy_patrols' => '\App\Models\EnemyPatrol',
            'dungeon_floor_switch_markers' => '\App\Models\DungeonFloorSwitchMarker',
            'dungeon_start_markers' => '\App\Models\DungeonStartMarker',
        ];

        $rootDir = base_path() . '/database/seeds/dungeondata/';
        $rootDirIterator = new FilesystemIterator($rootDir);

        // For each expansion
        foreach ($rootDirIterator as $expansionShortnameDir) {
            $this->command->info('Expansion ' . basename($expansionShortnameDir));
            $expansionDirIterator = new FilesystemIterator($expansionShortnameDir);

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
     * @param $filePath
     * @param $modelClassName \Illuminate\Database\Eloquent\Model
     * @return int The amount of models loaded from the file
     * @throws \Exception
     */
    private function _loadModelsFromFile($filePath, $modelClassName)
    {
        // $this->command->info('>> _loadModelsFromFile ' . $filePath . ' ' . $modelClassName);
        // Load contents
        $modelJson = file_get_contents($filePath);
        // Convert to models
        $modelsData = json_decode($modelJson, true);

        // Pre-fetch all valid columns to make the below loop a bit faster
        $modelColumns = Schema::getColumnListing($modelClassName::newModelInstance()->getTable());

        $attributeParsers = [
            // Generic
            new NestedModelRelationParser(),

            // Dungeon route
            new DungeonRoutePlayerRaceRelationParser(),
            new DungeonRoutePlayerClassRelationParser(),
            new DungeonRouteAffixGroupRelationParser(),

            // Enemy Pack
            new EnemyPackVerticesRelationParser(),

            // Enemy Patrol
            new EnemyPatrolVerticesRelationParser()
        ];

        // Do some php fuckery to make this a bit cleaner
        foreach ($modelsData as $modelData) {

            for ($i = 0; $i < count($modelData); $i++) {
                $keys = array_keys($modelData);
                $key = $keys[$i];
                $value = $modelData[$key];

                // $this->command->info(json_encode($key) . ' ' . json_encode($value));

                foreach ($attributeParsers as $attributeParser) {
                    /** @var $attributeParser RelationParser */
                    // Relations are always arrays, so exclude those that are not, then verify if the parser can handle this, then if it can, parse it
                    if (is_array($value) &&
                        $attributeParser->canParseModel($modelClassName) &&
                        $attributeParser->canParseRelation($key, $value)) {

                        $this->command->info(get_class($attributeParser) . ' can parse ' . $key);
                        $modelData = $attributeParser->parseRelation($modelClassName, $modelData, $key, $value);
                    }
                }

//                // The column may not be set due to objects appearing in this array that need to be de-normalized
                if (!in_array($key, $modelColumns)) {
                    unset($modelData[$key]);
                    $i--;
                }
            }

            // $this->command->info("Creating model " . json_encode($modelData));
            // Create and save a new instance to the database
            $modelClassName::create($modelData);
        }

        // $this->command->info('OK _loadModelsFromFile ' . $filePath . ' ' . $modelClassName);
        return count($modelsData);
    }

    protected function _rollback()
    {
        DB::table('npcs')->truncate();
        DB::table('enemies')->truncate();
        DB::table('enemy_packs')->truncate();
        DB::table('enemy_pack_vertices')->truncate();
        DB::table('enemy_patrols')->truncate();
        DB::table('enemy_patrol_vertices')->truncate();
        DB::table('dungeon_start_markers')->truncate();
        DB::table('dungeon_floor_switch_markers')->truncate();

        $demoRoutes = \App\Models\DungeonRoute::all()->where('demo', '=', true);

        foreach ($demoRoutes as $demoRoute) {
            try {
                /** @var $demoRoute \Illuminate\Database\Eloquent\Model */
                $demoRoute->delete();
            } catch (Exception $ex) {
                $this->command->error('Exception deleting demo dungeonroute');
            }
        }
    }
}
