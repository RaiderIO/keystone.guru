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
use Database\Factories\DungeonStartFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $mapping_version_id
 * @property int         $floor_id
 * @property int|null    $target_dungeon_id
 * @property float       $lat
 * @property float       $lng
 * @property string|null $comment
 *
 * @property bool     $raid                True if this start leads into a raid: its target dungeon when it has one, otherwise its own dungeon.
 * @property int|null $min_suggested_level The lowest suggested level of the dungeon this start leads into.
 * @property int|null $max_suggested_level The highest suggested level of the dungeon this start leads into.
 *
 * @property MappingVersion $mappingVersion
 * @property Floor          $floor
 * @property Dungeon|null   $targetDungeon
 *
 * @mixin Eloquent
 */
class DungeonStart extends Model implements HasLatLngInterface, MappingModelCloneableInterface, MappingModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasLatLng;
    /** @use HasFactory<DungeonStartFactory> */
    use HasFactory;
    use SeederModel;

    /**
     * Attributes describing the dungeon this start leads into, appended wherever a start is sent to the map.
     */
    public const array DESTINATION_ATTRIBUTES = [
        'raid',
        'min_suggested_level',
        'max_suggested_level',
    ];

    protected $hidden = [
        'mappingVersion',
        'floor',
        'targetDungeon',
        'laravel_through_key',
    ];

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'target_dungeon_id',
        'lat',
        'lng',
        'comment',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'mapping_version_id' => 'integer',
            'floor_id'           => 'integer',
            'target_dungeon_id'  => 'integer',
            'lat'                => 'float',
            'lng'                => 'float',
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
     * @return BelongsTo<Dungeon, $this>
     */
    public function targetDungeon(): BelongsTo
    {
        return $this->belongsTo(Dungeon::class, 'target_dungeon_id');
    }

    public function getRaidAttribute(): bool
    {
        return $this->getDestinationDungeon()->raid;
    }

    public function getMinSuggestedLevelAttribute(): ?int
    {
        return $this->getDestinationDungeon()->min_suggested_level;
    }

    public function getMaxSuggestedLevelAttribute(): ?int
    {
        return $this->getDestinationDungeon()->max_suggested_level;
    }

    /**
     * The dungeon this start leads into: its target dungeon when it has one, otherwise its own dungeon.
     */
    public function getDestinationDungeon(): Dungeon
    {
        return $this->targetDungeon ?? $this->floor->dungeon;
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }

    /**
     * @param int $index The position of this start among its mapping version's starts, used when it has no comment.
     */
    public function getDisplayText(int $index): string
    {
        return ($this->comment ?? '') !== '' ?
            (string)__($this->comment) :
            sprintf('%s #%d', __('mapicontypes.dungeon_start'), $index + 1);
    }
}
