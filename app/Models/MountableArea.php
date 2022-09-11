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
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return array
     */
    public function getIntersections(float $x1, float $y1, float $x2, float $y2): array
    {
        $vertices = json_decode($this->vertices_json, true);

        $result = [];
        foreach ($vertices as $vertexIndex => $vertex) {
            // Loop back around if needed
            $nextVertex = $vertices[$vertexIndex + 1] ?? $vertices[0];
            // Convert vertex locations to ingame locations
            $vertexIngameLocation     = $this->floor->calculateIngameLocationForMapLocation($vertex['lat'], $vertex['lng']);
            $nextVertexIngameLocation = $this->floor->calculateIngameLocationForMapLocation($nextVertex['lat'], $nextVertex['lng']);

            // Calculate the intersection between the line and the line of the vertex
            $intersection = intersection($x1, $y1, $x2, $y2, $vertexIngameLocation['x'], $vertexIngameLocation['y'], $nextVertexIngameLocation['x'], $nextVertexIngameLocation['y']);

            if ($intersection !== null) {
                $result[] = $this->floor->calculateMapLocationForIngameLocation($intersection['x'], $intersection['y']);
            }
        }

        return $result;
    }
}
