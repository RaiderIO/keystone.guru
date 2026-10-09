<?php

namespace App\Models;

use App\Logic\Utils\HtmlSanitizer;
use App\Models\Floor\Floor;
use App\Models\Interfaces\HasLatLngInterface;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasLatLng;
use App\Models\Traits\SeederModel;
use Database\Factories\DungeonTransportFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One end of a portal, boat, shortcut or other transport. Clicking it takes the viewer to its linked partner.
 *
 * @property int         $id
 * @property int         $mapping_version_id
 * @property int         $floor_id
 * @property int         $map_icon_type_id
 * @property int|null    $linked_dungeon_transport_id
 * @property int|null    $target_dungeon_id
 * @property string|null $link_key
 * @property string|null $path_vertices_json
 * @property float       $lat
 * @property float       $lng
 * @property string|null $comment
 *
 * @property MappingVersion        $mappingVersion
 * @property Floor                 $floor
 * @property MapIconType           $mapIconType
 * @property DungeonTransport|null $linkedDungeonTransport
 * @property Dungeon|null          $targetDungeon
 *
 * @mixin Eloquent
 */
class DungeonTransport extends Model implements HasLatLngInterface, MappingModelCloneableInterface, MappingModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasLatLng;
    /** @use HasFactory<DungeonTransportFactory> */
    use HasFactory;
    use SeederModel;

    protected $hidden = [
        'mappingVersion',
        'floor',
        'mapIconType',
        'linkedDungeonTransport',
        'targetDungeon',
        'laravel_through_key',
    ];

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'map_icon_type_id',
        'linked_dungeon_transport_id',
        'target_dungeon_id',
        'link_key',
        'path_vertices_json',
        'lat',
        'lng',
        'comment',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'mapping_version_id'          => 'integer',
            'floor_id'                    => 'integer',
            'map_icon_type_id'            => 'integer',
            'linked_dungeon_transport_id' => 'integer',
            'target_dungeon_id'           => 'integer',
            'lat'                         => 'float',
            'lng'                         => 'float',
        ];
    }

    public function setCommentAttribute(?string $value): void
    {
        $this->attributes['comment'] = $value === null ? null : new HtmlSanitizer()->stripAllTags($value);
    }

    /**
     * @return BelongsTo<MappingVersion, $this>
     */
    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    /**
     * @return BelongsTo<Floor, $this>
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return BelongsTo<MapIconType, $this>
     */
    public function mapIconType(): BelongsTo
    {
        return $this->belongsTo(MapIconType::class);
    }

    /**
     * @return BelongsTo<DungeonTransport, $this>
     */
    public function linkedDungeonTransport(): BelongsTo
    {
        return $this->belongsTo(DungeonTransport::class, 'linked_dungeon_transport_id');
    }

    /**
     * @return BelongsTo<Dungeon, $this>
     */
    public function targetDungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class, 'target_dungeon_id');
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }
}
