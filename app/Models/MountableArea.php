<?php

namespace App\Models;

use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $mapping_version_id
 * @property int $floor_id
 * @property int|null $speed
 * @property string $vertices_json
 *
 * @property Floor $floor
 *
 * @mixin Eloquent
 */
class MountableArea extends CacheModel implements MappingModelInterface, MappingModelCloneableInterface
{
    use CloneForNewMappingVersionNoRelations;

    public $timestamps = false;
    public $fillable = [
        'mapping_version_id',
        'floor_id',
        'speed',
        'vertices_json',
    ];

    public $hidden = [
        'floor',
    ];

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return array
     */
    public function getVerticesAsIngameCoordinates(): array
    {
        $vertices = json_decode($this->vertices_json, true);

        $result = [];
        foreach ($vertices as $vertex) {
            $result[] =
                array_values($this->floor->calculateIngameLocationForMapLocation($vertex['lat'], $vertex['lng']));
        }

        return $result;
    }

    /**
     * @param array $p1
     * @return bool
     */
    public function contains(array $p1): bool
    {
        return polygonContainsPoint($p1, json_decode($this->vertices_json, true));
    }

    /**
     * @param array{lat: float, lng: float} $p1
     * @param array{lat: float, lng: float} $p2
     * @return array{array{lat: float, lng: float}}
     */
    public function getIntersections(array $p1, array $p2): array
    {
        $vertices = json_decode($this->vertices_json, true);

        $result = [];
        foreach ($vertices as $vertexIndex => $vertex) {
            // Loop back around if needed
            $nextVertex = $vertices[$vertexIndex + 1] ?? $vertices[0];
            // Calculate the intersection between the line and the line of the vertex
            $intersection = intersection(
                $p1, $p2,
                $vertex, $nextVertex
            );

            if ($intersection !== null) {
                $result[] = $intersection;
            }
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getSpeedOrDefault(): int
    {
        return $this->speed ?? config('keystoneguru.character.mounted_movement_speed_yards_second');
    }

    /**
     * @return int|null
     */
    public function getDungeonId(): ?int
    {
        return optional($this->floor)->dungeon_id ?? null;
    }
}
