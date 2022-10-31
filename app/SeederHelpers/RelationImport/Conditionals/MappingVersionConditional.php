<?php


namespace App\SeederHelpers\RelationImport\Conditionals;

use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use App\SeederHelpers\RelationImport\Mapping\RelationMapping;
use Exception;
use Illuminate\Support\Collection;

/**
 * Determines if we can import this model based on its mapping version. If this model has a newer mapping version than
 * the one that currently exists in the database, we may import it. Otherwise, we shouldn't import it.
 *
 *
 * @package App\SeederHelpers\RelationImport\Conditionals
 * @author Wouter
 * @since 27/10/2022
 */
class MappingVersionConditional implements ConditionalInterface
{
    /** @var Collection */
    private Collection $floorCache;

    /** @var Collection */
    private Collection $dungeonCache;

    public function __construct()
    {
        $this->floorCache   = collect();
        $this->dungeonCache = collect();
    }

    /**
     * @param RelationMapping $relationMapping
     * @param array $modelData
     * @return bool
     * @throws Exception
     */
    public function shouldParseModel(RelationMapping $relationMapping, array $modelData): bool
    {
        if (isset($modelData['dungeon_id'])) {
            $dungeon = $this->getDungeonById($modelData['dungeon_id']);
        } else if (isset($modelData['floor_id'])) {
            $dungeon = $this->getDungeonById($this->getFloorById($modelData['floor_id'])->dungeon_id);
        } else {
            throw new Exception(sprintf('Unable to find dungeon in model data! %s', json_encode($modelData)));
        }

        $modelMappingVersion = MappingVersion::findOrFail($modelData['mapping_version_id']);

        // Only import this model if it's a version upgrade
        return $modelMappingVersion->version > $dungeon->getCurrentMappingVersion()->version;
    }

    /**
     * @param int $id
     * @return Floor
     */
    private function getFloorById(int $id): Floor
    {
        if ($this->floorCache->has($id)) {
            $floor = $this->floorCache->get($id);
        } else {
            $floor = Floor::findOrFail($id);
            $this->floorCache->put($id, $floor);
        }

        return $floor;
    }

    /**
     * @param int $id
     * @return Dungeon
     */
    private function getDungeonById(int $id): Dungeon
    {
        if ($this->dungeonCache->has($id)) {
            $dungeon = $this->dungeonCache->get($id);
        } else {
            $dungeon = Dungeon::findOrFail($id);
            $this->dungeonCache->put($id, $dungeon);
        }

        return $dungeon;
    }
}
