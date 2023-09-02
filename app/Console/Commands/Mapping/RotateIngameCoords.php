<?php

namespace App\Console\Commands\Mapping;

use App\Models\Dungeon;
use Illuminate\Console\Command;

class RotateIngameCoords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:rotateingamecoords {dungeonId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Commits any saved mapping to Git';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dungeon = Dungeon::findOrFail((int)$this->argument('dungeonId'));

        foreach ($dungeon->floors as $floor) {
            $newCoordinates = [
                'ingame_min_x' => $floor->ingame_min_y * -1,
                'ingame_min_y' => $floor->ingame_min_x,
                'ingame_max_x' => $floor->ingame_max_y * -1,
                'ingame_max_y' => $floor->ingame_max_x,
            ];

            $floor->update($newCoordinates);
            $this->info(sprintf('- Rotated floor %d 90 degrees: %s', $floor->id, json_encode($newCoordinates)));
        }
    }
}
