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
            $this->command->info('Expansion: ' . basename($expansionShortnameDir));
            $expansionDirIterator = new FilesystemIterator($expansionShortnameDir);
            // For each dungeon inside an expansion dir
            foreach ($expansionDirIterator as $dungeonKeyDir) {
                // Temp to reduce spam
                if (!str_contains($dungeonKeyDir, "hallsofvalor")) {
                    continue;
                }
                $this->command->info('Importing dungeon: ' . basename($dungeonKeyDir));

                // Import NPCs
                $this->command->info('Importing NPCs...');
                // Get contents
                $count = $this->_loadModelsFromFile($dungeonKeyDir . '/npcs.json', '\App\Models\Npc');
                $this->command->info(sprintf('Imported %s NPCs', $count));


                $floorDirIterator = new FilesystemIterator($dungeonKeyDir);
                // For each floor inside a dungeon dir
                foreach ($floorDirIterator as $floorDir) {
                    // Skip loose files (npcs.json looking at you)
                    if (is_dir($floorDir)) {
                        $this->command->info('Importing floor ' . basename($floorDir));

                        $importFileIterator = new FilesystemIterator($floorDir);
                        // For each file inside a floor
                        foreach ($importFileIterator as $importFile) {
                            $tableName = basename($importFile, '.json');
                            $this->command->info('Importing ' . $tableName);

                            if (!array_key_exists($tableName, $nameMapping)) {
                                $this->command->error('Unable to find table->model mapping for file ' . $importFile);
                            } else {
                                $count = $this->_loadModelsFromFile($importFile, $nameMapping[$tableName]);
                                $this->command->info(sprintf('Imported %s %s', $count, $tableName));
                            }

                        }
                    }
                }
            }
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
        $this->command->info('>> _loadModelsFromFile ' . $filePath . ' ' . $modelClassName);
        // Load contents
        $modelJson = file_get_contents($filePath);
        // Convert to models
        $modelsData = json_decode($modelJson, true);

        // Do some php fuckery to make this a bit cleaner
        foreach ($modelsData as $modelData) {
            foreach($modelData as $key => $value){
                // Encountered an object; we gotta de-normalize it
                if(is_array($value)){
                    $this->command->info(json_encode($modelData));
                    /** @var $value \Illuminate\Database\Eloquent\Model */
                    $modelData[$key . '_id'] = $value['id'];

                    // Remove the object; we can now save the thing in the DB properly
                    unset($modelData[$key]);
                }
            }
            // Create and save a new instance to the database
            $modelClassName::create($modelData);
        }

        $this->command->info('OK _loadModelsFromFile ' . $filePath . ' ' . $modelClassName);
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
    }
}
