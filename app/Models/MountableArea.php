<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $floor_id
 * @property string $vertices_json
 *
 * @property Floor $floor
 *
 * @mixin Eloquent
 */
class MountableArea extends CacheModel
{
    public $timestamps = false;
    public $fillable = [
        'floor_id',
        'vertices_json',
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
}
