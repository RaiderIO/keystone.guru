<?php

namespace App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasLatLng;
use App\Models\Traits\HasLinkedAwakenedObelisk;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                 $id
 * @property int|null            $mapping_version_id
 * @property int                 $floor_id
 * @property int|null            $dungeon_route_id
 * @property int|null            $team_id
 * @property int                 $map_icon_type_id
 * @property float               $lat
 * @property float               $lng
 * @property string              $comment
 * @property bool                $permanent_tooltip
 * @property int                 $seasonal_index
 *
 * @property MappingVersion|null $mappingVersion
 * @property Floor               $floor
 * @property DungeonRoute|null   $dungeonRoute
 * @property Team|null           $team
 * @property MapIconType         $mapIconType
 *
 * @mixin Eloquent
 */
class MapIcon extends Model implements MappingModelCloneableInterface, MappingModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasLatLng;
    use HasLinkedAwakenedObelisk;

    protected $visible = [
        'id',
        'mapping_version_id',
        'floor_id',
        'dungeon_route_id',
        'team_id',
        'map_icon_type_id',
        'linked_awakened_obelisk_id',
        'is_admin',
        'lat',
        'lng',
        'comment',
        'permanent_tooltip',
        'seasonal_index',
    ];

    protected $fillable = [
        'mapping_version_id',
        'floor_id',
        'dungeon_route_id',
        'team_id',
        'map_icon_type_id',
        'lat',
        'lng',
        'comment',
        'permanent_tooltip',
        'seasonal_index',
    ];

    protected $appends = ['linked_awakened_obelisk_id', 'is_admin'];

    protected $casts = [
        'mapping_version_id'         => 'integer',
        'floor_id'                   => 'integer',
        'dungeon_route_id'           => 'integer',
        'team_id'                    => 'integer',
        'map_icon_type_id'           => 'integer',
        'lat'                        => 'float',
        'lng'                        => 'float',
        'permanent_tooltip'          => 'integer',
        'seasonal_index'             => 'integer',
        'linked_awakened_obelisk_id' => 'integer',
    ];

    protected $with = ['mapIconType', 'linkedawakenedobelisks'];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function dungeonRoute(): BelongsTo
    {
        return $this->belongsTo(DungeonRoute::class);
    }

    public function mapIconType(): BelongsTo
    {
        return $this->belongsTo(MapIconType::class);
    }

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->mapping_version_id !== null;
    }

    public function isAwakenedObelisk(): bool
    {
        return in_array($this->map_icon_type_id, [
            MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC],
            MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED],
            MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED],
            MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL],
        ]);
    }

    public function getDungeonId(): ?int
    {
        return $this->floor?->dungeon_id ?? null;
    }
}
