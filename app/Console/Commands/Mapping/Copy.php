<?php

namespace App\Console\Commands\Mapping;

use App\Models\Dungeon;
use App\Service\Mapping\MappingService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Copy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:copy {sourceDungeonId} {targetDungeonId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copies a mapping version to another dungeon';

    /**
     * Execute the console command.
     */
    public function handle(MappingService $mappingService): int
    {
        $sourceDungeonId = $this->argument('sourceDungeonId');
        $targetDungeonId = $this->argument('targetDungeonId');

        $sourceDungeon = Dungeon::findOrFail($sourceDungeonId);
        $targetDungeon = Dungeon::findOrFail($targetDungeonId);

        if ($sourceDungeonId !== $targetDungeonId) {
            if ($sourceDungeon->floors()->count() !== $targetDungeon->floors()->count()) {
                $this->error('Unable to migrate mapping version to different dungeon - floor count does not match');

                return -1;
            }
        }

        // Create a new mapping version for our dungeon
        $newMappingVersion = $mappingService->createNewMappingVersionFromPreviousMapping($sourceDungeon);

        $newMappingVersion->update([
            'dungeon_id' => $targetDungeon->id,
            'version'    => ($sourceDungeon->currentMappingVersion?->version ?? 0) + 1,
        ]);

        if ($sourceDungeonId !== $targetDungeonId) {
            // Try to migrate all floors as good as we can
            $relations = [
                'dungeonFloorSwitchMarkers',
                'enemies',
                'enemyPacks',
                'enemyPatrols',
                'mapIcons',
                'mountableAreas',
                'floorUnions', //floor_id, target_floor
                'floorUnionAreas',
                'npcEnemyForces',
            ];

            foreach ($relations as $relation) {
                /** @var Collection|Model[] $entities */
                $entities = $newMappingVersion->getRelation($relation)->get();
                foreach ($entities as $entity) {
                    $entity->update([
                        'floor_id' => '',
                    ]);
                }
            }
        }

        return 0;
    }
}
