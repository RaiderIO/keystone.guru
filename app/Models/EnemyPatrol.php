<?php

namespace App\Models;

use App\Logic\MDT\Entity\MDTPatrol;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

/**
 * @property int      $id
 * @property int      $mapping_version_id
 * @property int      $floor_id
 * @property int      $polyline_id
 * @property int|null $mdt_polyline_id    The polyline that represents this patrol in MDT
 * @property int|null $mdt_npc_id         Keeps track of which enemy this patrol was assigned to in MDT
 * @property int|null $mdt_id
 * @property string   $teeming
 * @property string   $faction
 *
 * @property MappingVersion $mappingVersion
 * @property Floor|null     $floor
 * @property Polyline|null  $polyline
 * @property Polyline|null  $mdtPolyline
 *
 * @mixin Eloquent
 */
class EnemyPatrol extends CacheModel implements MappingModelCloneableInterface, MappingModelInterface
{
    use SeederModel;

    public $visible = [
        'id',
        'mapping_version_id',
        'floor_id',
        'teeming',
        'faction',
        'polyline',
    ];

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'polyline_id',
        'mdt_polyline_id',
        'mdt_npc_id',
        'mdt_id',
        'teeming',
        'faction',
    ];

    public $with = ['polyline'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id'                 => 'integer',
            'mapping_version_id' => 'integer',
            'floor_id'           => 'integer',
            'polyline_id'        => 'integer',
            'mdt_polyline_id'    => 'integer',
            'mdt_npc_id'         => 'integer',
            'mdt_id'             => 'integer',
        ];
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
     * @return HasOne<Polyline, $this>
     */
    public function polyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')
            ->where('model_class', static::class);
    }

    /**
     * @return HasOne<Polyline, $this>
     */
    public function mdtPolyline(): HasOne
    {
        return $this->hasOne(Polyline::class, 'model_id')
            ->where('model_class', MDTPatrol::class);
    }

    public function getDungeonId(): ?int
    {
        return $this->floor?->dungeon_id;
    }

    public function cloneForNewMappingVersion(
        MappingVersion         $mappingVersion,
        ?MappingModelInterface $newParent = null,
    ): EnemyPatrol {
        /** @var static $clonedEnemyPatrol */
        $clonedEnemyPatrol         = clone $this;
        $clonedEnemyPatrol->exists = false;
        unset($clonedEnemyPatrol->id);
        $clonedEnemyPatrol->mapping_version_id = $mappingVersion->id;
        $clonedEnemyPatrol->save();

        $clonedPolyLine = $this->polyline?->cloneForNewMappingVersion($mappingVersion, $clonedEnemyPatrol);
        $this->load('mdtPolyline');
        $clonedMdtPolyLine = $this->mdtPolyline?->cloneForNewMappingVersion($mappingVersion, $clonedEnemyPatrol);
        $clonedEnemyPatrol->update([
            'polyline_id'     => $clonedPolyLine->id,
            'mdt_polyline_id' => $clonedMdtPolyLine?->id,
        ]);

        return $clonedEnemyPatrol;
    }

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        // Delete patrol properly if it gets deleted
        static::deleting(static function (EnemyPatrol $enemyPatrol) {
            $enemyPatrol->polyline?->delete();

            $enemyPatrol->load('mdtPolyline');
            $enemyPatrol->mdtPolyline?->delete();
        });
    }
}
