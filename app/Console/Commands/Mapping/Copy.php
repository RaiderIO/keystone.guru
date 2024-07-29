<?php

namespace App\Console\Commands\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
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
    protected $signature = 'mapping:copy {sourceDungeon} {targetDungeon}';

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
        $sourceDungeonKey = $this->argument('sourceDungeon');
        $targetDungeonKey = $this->argument('targetDungeon');

        $sourceDungeon = Dungeon::firstWhere('key', $sourceDungeonKey);
        $targetDungeon = Dungeon::firstWhere('key', $targetDungeonKey);

        if ($sourceDungeonKey !== $targetDungeonKey) {
            if ($sourceDungeon->floors()->count() !== $targetDungeon->floors()->count()) {
                $this->error('Unable to migrate mapping version to different dungeon - floor count does not match');

                return -1;
            }
        }

        // Create a new mapping version for our dungeon
        $newMappingVersion = $mappingService->createNewMappingVersionFromPreviousMapping($sourceDungeon);

        $newMappingVersion->update([
            'dungeon_id' => $targetDungeon->id,
            'version'    => ($targetDungeon->currentMappingVersion?->version ?? 0) + 1,
        ]);

        if ($sourceDungeonKey !== $targetDungeonKey) {
            // Construct a floor mapping
            $sourceDungeonFloors = $sourceDungeon->floors()->orderBy('index')->get()->keyBy('index');
            $targetDungeonFloors = $targetDungeon->floors()->orderBy('index')->get()->keyBy('index');

            /** @var Collection<Floor> $floorIdMapping */
            $floorIdMapping = $sourceDungeonFloors->mapWithKeys(function (Floor $floor) use ($targetDungeonFloors) {
                /** @var Floor $targetFloor */
                $targetFloor = $targetDungeonFloors->get($floor->index);

                return [$floor->id => $targetFloor->id];
            });

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

            $newMappingVersion->load($relations);

            foreach ($relations as $relation) {
                $this->info(sprintf('Copying %s...', $relation));
                /** @var Collection<Model> $entities */
                $entities = $newMappingVersion->getRelation($relation);

                foreach ($entities as $entity) {
                    /** @noinspection PhpPossiblePolymorphicInvocationInspection Trust me bro */
                    $attributes = [
                        'floor_id' => $floorIdMapping->get($entity->floor_id),
                    ];

                    if ($entity instanceof DungeonFloorSwitchMarker) {
                        $attributes['source_floor_id'] = $floorIdMapping->get($entity->source_floor_id);
                        $attributes['target_floor_id'] = $floorIdMapping->get($entity->target_floor_id);
                    } else if ($entity instanceof FloorUnion) {
                        $attributes['target_floor_id'] = $floorIdMapping->get($entity->target_floor_id);
                    }

                    $entity->update($attributes);
                }
            }
        }

        return 0;
    }
}
