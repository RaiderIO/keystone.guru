<?php

namespace App\Models;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
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
     * @param LatLng $latLng
     * @return bool
     */
    public function contains(LatLng $latLng): bool
    {
        return polygonContainsPoint($latLng->toArray(), json_decode($this->vertices_json, true));
    }

    /**
     * @param LatLng $latLngA
     * @param LatLng $latLngB
     * @return array{array{lat: float, lng: float}}
     */
    public function getIntersections(LatLng $latLngA, LatLng $latLngB): array
    {
        $vertices = json_decode($this->vertices_json, true);

        $result = [];
        foreach ($vertices as $vertexIndex => $vertex) {
            // Loop back around if needed
            $nextVertex = $vertices[$vertexIndex + 1] ?? $vertices[0];
            // Calculate the intersection between the line and the line of the vertex
            $intersection = intersection(
                $latLngA->toArray(), $latLngB->toArray(),
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
