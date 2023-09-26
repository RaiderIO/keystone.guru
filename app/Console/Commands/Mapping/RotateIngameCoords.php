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
    protected $signature = 'mapping:rotateingamecoords {dungeon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotates the in-game coordinates of all floors by 90 degrees.';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::where('key', $this->argument('dungeon'))->first();

        if ($dungeon === null) {
            throw new \Exception('Unable to find dungeon!');
        }

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
