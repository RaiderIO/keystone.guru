<?php

namespace App\Console\Commands\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor;
use App\Models\MapIcon;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Npc;
use App\Models\Spell;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class Restore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:restore {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restores a part of the mapping based on the mapping_change_logs table. I once messed up and needed this.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = (int)$this->argument('id');

        $changeLogs = MappingChangeLog::where('id', '>=', $id)->get();

        foreach ($changeLogs as $changeLog) {

            try {
                if ($changeLog->model_class === 'App\Models\Enemy') {
                    // This mob was marked as inspiring
                    if (strpos($changeLog->after_model, 'inspiring') !== false) {
                        $enemy = Enemy::findOrFail($changeLog->model_id);
                        $enemy->seasonal_type = 'inspiring';
                        $enemy->save();
                        $this->info(sprintf('Successfully restored %s -> ID = %s', $changeLog->model_class, $changeLog->model_id));
                    }
                } else {
                    // If JSON parsed properly
                    $properties = json_decode($changeLog->after_model);
                    if ($properties !== null) {
                        /** @var Model $modelClass */
                        $modelClass = new $changeLog->model_class;
                        foreach ($properties as $property => $value) {
                            // Prevent 'this column does not exist' errors -> https://stackoverflow.com/questions/51703381/check-if-column-exist-in-laravel-models-table-and-then-apply-condition
                            if ($modelClass->getConnection()->getSchemaBuilder()->hasColumn($modelClass->getTable(), $property)) {
                                // We don't control the IDs of map icons
                                if ($property === 'id' && $changeLog->model_class === 'App\Models\MapIcon') {
                                    continue;
                                }
                                $modelClass->$property = $value;
                            }
                        }
                        $modelClass->save();
                        $this->info(sprintf('Successfully restored %s -> ID = %s', $changeLog->model_class, $changeLog->model_id));
                    } else {
                        $this->error(sprintf('Unable to restore model %s -> ID = %s', $changeLog->model_class, $changeLog->model_id));
                    }
                }

            } catch (\Exception $ex) {

                $this->error(sprintf('Unable to restore model %s -> ID = %s', $changeLog->model_class, $changeLog->model_id));
            }

        }


        return 0;
    }
}
