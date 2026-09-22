<?php

namespace App\Models;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Interfaces\HasPolylineInterface;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasPolyline;
use App\Models\Traits\SeederModel;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int           $id
 * @property int           $mapping_version_id
 * @property int           $floor_id
 * @property int|null      $speed
 * @property int|null      $polyline_id
 * @property Floor         $floor
 * @property Polyline|null $polyline
 *
 * @mixin Eloquent
 */
class MountableArea extends Model implements HasPolylineInterface, MappingModelCloneableInterface, MappingModelInterface
{
    use HasPolyline;
    use SeederModel;

    /** Mirrors the front-end's c.map.mountablearea.color */
    public const string DEFAULT_COLOR = '#eb4934';

    public const int DEFAULT_WEIGHT = 1;

    public $timestamps = false;

    public $fillable = [
        'mapping_version_id',
        'floor_id',
        'speed',
        'polyline_id',
    ];

    public $hidden = [
        'floor',
        'polyline_id',
        'vertices_json',
    ];

    /** Every reader of a mountable area needs its shape */
    public $with = ['polyline'];

    protected function casts(): array
    {
        return [
            'polyline_id' => 'integer',
        ];
    }

    /** @return BelongsTo<MappingVersion, $this> */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    /** @return BelongsTo<Floor, $this> */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function contains(CoordinatesServiceInterface $coordinatesService, LatLng $latLng): bool
    {
        $vertices = $this->getVertices();

        return !empty($vertices) && $coordinatesService->polygonContainsPoint($latLng, $vertices);
    }

    /**
     * @return LatLng[]
     */
    public function getIntersections(
        CoordinatesServiceInterface $coordinatesService,
        LatLng                      $latLngA,
        LatLng                      $latLngB,
    ): array {
        $vertices = $this->getVertices();

        $result = [];
        foreach ($vertices as $vertexIndex => $vertex) {
            // Loop back around if needed
            $nextVertex = $vertices[$vertexIndex + 1] ?? $vertices[0];
            // Calculate the intersection between the line and the line of the vertex
            $intersection = $coordinatesService->intersection(
                $latLngA,
                $latLngB,
                LatLng::fromArray($vertex),
                LatLng::fromArray($nextVertex),
            );

            if ($intersection !== null) {
                $result[] = $intersection;
            }
        }

        return $result;
    }

    public function getSpeedOrDefault(): int
    {
        return $this->speed ?? config('keystoneguru.character.mounted_movement_speed_yards_second');
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }

    public function cloneForNewMappingVersion(
        MappingVersion         $mappingVersion,
        ?MappingModelInterface $newParent = null,
    ): MountableArea {
        /** @var static $clonedMountableArea */
        $clonedMountableArea         = clone $this;
        $clonedMountableArea->exists = false;
        unset($clonedMountableArea->id, $clonedMountableArea->vertices_json);
        $clonedMountableArea->mapping_version_id = $mappingVersion->id;
        $clonedMountableArea->save();

        $clonedPolyline = $this->polyline?->cloneForNewMappingVersion($mappingVersion, $clonedMountableArea);
        $clonedMountableArea->update(['polyline_id' => $clonedPolyline?->id]);
        $clonedMountableArea->setRelation('polyline', $clonedPolyline);

        return $clonedMountableArea;
    }

    /**
     * @return array<int, array{lat: float, lng: float}>
     */
    private function getVertices(): array
    {
        return json_decode($this->polyline->vertices_json ?? '[]', true) ?? [];
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(static function (MountableArea $mountableArea) {
            $mountableArea->polyline?->delete();
        });
    }
}
