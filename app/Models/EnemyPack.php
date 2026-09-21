<?php

namespace App\Models;

use App\Models\Floor\Floor;
use App\Models\Interfaces\HasPolylineInterface;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasPolyline;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property int      $id
 * @property int      $mapping_version_id
 * @property int      $floor_id
 * @property int      $group
 * @property string   $teeming
 * @property string   $faction
 * @property string   $label
 * @property int|null $polyline_id
 *
 * @property Floor                          $floor
 * @property Polyline|null                  $polyline
 * @property EloquentCollection<int, Enemy> $enemies
 *
 * @mixin Eloquent
 */
class EnemyPack extends Model implements HasPolylineInterface, MappingModelCloneableInterface, MappingModelInterface
{
    use HasPolyline;
    use SeederModel;

    /** Mirrors the front-end's c.map.enemypack.defaultColor() */
    public const string DEFAULT_COLOR = '#5993D2';

    public const int DEFAULT_WEIGHT = 1;

    public $timestamps = false;

    public $with = ['polyline'];

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'group',
        'teeming',
        'faction',
        'label',
        'polyline_id',
    ];

    protected $hidden = [
        'mappingVersion',
        'floor',
        'polyline_id',
        'color',
        'color_animated',
        'vertices_json',
    ];

    protected function casts(): array
    {
        return [
            'mapping_version_id' => 'integer',
            'floor_id'           => 'integer',
            'group'              => 'integer',
            'polyline_id'        => 'integer',
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

    /** @return HasMany<Enemy, $this> */
    public function enemies(): HasMany
    {
        return $this->hasMany(Enemy::class);
    }

    /**
     * @return EloquentCollection<int, Enemy>
     */
    public function getEnemiesWithSeasonalType(string $seasonalType): EloquentCollection
    {
        return $this->enemies()->where('seasonal_type', $seasonalType)->get();
    }

    public function getDungeonId(): ?int
    {
        return $this->floor->dungeon_id;
    }

    public function cloneForNewMappingVersion(
        MappingVersion         $mappingVersion,
        ?MappingModelInterface $newParent = null,
    ): EnemyPack {
        /** @var static $clonedEnemyPack */
        $clonedEnemyPack         = clone $this;
        $clonedEnemyPack->exists = false;
        unset($clonedEnemyPack->id, $clonedEnemyPack->color, $clonedEnemyPack->color_animated, $clonedEnemyPack->vertices_json);
        $clonedEnemyPack->mapping_version_id = $mappingVersion->id;
        $clonedEnemyPack->save();

        $clonedPolyline = $this->polyline?->cloneForNewMappingVersion($mappingVersion, $clonedEnemyPack);
        $clonedEnemyPack->update(['polyline_id' => $clonedPolyline?->id]);
        $clonedEnemyPack->setRelation('polyline', $clonedPolyline);

        return $clonedEnemyPack;
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(static function (EnemyPack $enemyPack) {
            $enemyPack->polyline?->delete();
        });
    }
}
