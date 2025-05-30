<?php

namespace App\Models;

use App\Models\Floor\Floor;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\HasVertices;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int            $id
 * @property int            $mapping_version_id
 * @property int            $floor_id
 * @property int            $polyline_id
 * @property int|null       $mdt_npc_id Keeps track of which enemy this patrol was assigned to in MDT
 * @property int|null       $mdt_id
 * @property string         $teeming
 * @property string         $faction
 *
 * @property MappingVersion $mappingVersion
 * @property Floor          $floor
 * @property Polyline       $polyline
 *
 * @mixin Eloquent
 */
class EnemyPatrol extends CacheModel implements MappingModelCloneableInterface, MappingModelInterface
{
    use SeederModel;

    public $visible = ['id', 'mapping_version_id', 'floor_id', 'teeming', 'faction', 'polyline'];

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'polyline_id',
        'mdt_npc_id',
        'mdt_id',
        'teeming',
        'faction',
    ];

    protected $casts = [
        'id'                 => 'integer',
        'mapping_version_id' => 'integer',
        'floor_id'           => 'integer',
        'polyline_id'        => 'integer',
        'mdt_npc_id'         => 'integer',
        'mdt_id'             => 'integer',
    ];

    public $with = ['polyline'];

    public $timestamps = false;

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function polyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')
            ->where('model_class', static::class);
    }

    public function getDungeonId(): ?int
    {
        return $this->floor?->dungeon_id ?? null;
    }

    public function cloneForNewMappingVersion(MappingVersion $mappingVersion, ?MappingModelInterface $newParent = null): EnemyPatrol
    {
        /** @var EnemyPatrol|MappingModelInterface $clonedEnemyPatrol */
        $clonedEnemyPatrol                     = clone $this;
        $clonedEnemyPatrol->exists             = false;
        $clonedEnemyPatrol->id                 = null;
        $clonedEnemyPatrol->mapping_version_id = $mappingVersion->id;
        $clonedEnemyPatrol->save();

        $clonedPolyLine = $this->polyline->cloneForNewMappingVersion($mappingVersion, $clonedEnemyPatrol);
        $clonedEnemyPatrol->update(['polyline_id' => $clonedPolyLine->id]);

        return $clonedEnemyPatrol;
    }

    protected static function boot(): void
    {
        parent::boot();

        // Delete patrol properly if it gets deleted
        static::deleting(static function (EnemyPatrol $enemyPatrol) {
            if ($enemyPatrol->polyline !== null) {
                $enemyPatrol->polyline->delete();
            }
        });
    }
}
