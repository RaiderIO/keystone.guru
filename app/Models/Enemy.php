<?php

namespace App\Models;

use App\Models\Floor\Floor;
use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Traits\HasLatLng;
use App\Models\Traits\Reportable;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int                         $id
 * @property int                         $mapping_version_id
 * @property int|null                    $enemy_pack_id
 * @property int|null                    $enemy_patrol_id
 * @property int|null                    $npc_id
 * @property int                         $floor_id
 * @property int|null                    $mdt_id The ID in MDT (clone index) that this enemy is coupled to
 * @property int|null                    $mdt_npc_id The ID of the NPC in MDT that this enemy is coupled to. Usually this will be the same - but MDT sometimes makes mistakes which will require a different NPC to be coupled.
 * @property int|null                    $exclusive_enemy_id The ID of the enemy that this enemy is exclusive to. This means that this enemy will not be selectable if the exclusive enemy is selected in a pull.
 * @property int|null                    $mdt_scale The scale that MDT assigned to this particular enemy.
 * @property string|null                 $mdt_x The X position that MDT assigned to this enemy on import.
 * @property string|null                 $mdt_y The Y position that MDT assigned to this enemy on import.
 * @property string                      $seasonal_type The type of seasonal effect this enemy has. Awakened to signify an Awakened enemy, Inspiring to signify an Inspiring enemy
 * @property int                         $seasonal_index Shows/hides this enemy based on the seasonal index as defined in Affix Group. If they match, the enemy is shown, otherwise hidden. If not set enemy is always shown.
 * @property int                         $mdt_npc_index The index of the NPC in MDT (not saved in DB)
 * @property int                         $enemy_id Only used for temp MDT enemies (not saved in DB)
 * @property bool                        $is_mdt Only used for temp MDT enemies (not saved in DB)
 * @property string                      $teeming
 * @property string                      $faction
 * @property bool                        $required
 * @property bool                        $skippable
 * @property bool                        $hyper_respawn
 * @property int|null                    $kill_priority Used for determining the group in which enemies are scanned for and killed when parsing a combat log. Null = default, negative = lower priority, positive = higher priority
 * @property int|null                    $enemy_forces_override
 * @property int|null                    $enemy_forces_override_teeming
 * @property int|null                    $dungeon_difficulty Show this enemy only in this difficulty setting (null is show always)
 * @property float                       $lat
 * @property float                       $lng
 *
 * @property EnemyPack|null              $enemyPack
 * @property Npc|null                    $npc
 * @property Floor                       $floor
 * @property EnemyPatrol|null            $enemyPatrol
 * @property Enemy|null                  $exclusiveEnemy
 * @property MappingVersion              $mappingVersion
 * @property Collection<EnemyActiveAura> $enemyActiveAuras
 *
 * @mixin Eloquent
 */
class Enemy extends CacheModel implements MappingModelCloneableInterface, MappingModelInterface
{
    use CloneForNewMappingVersionNoRelations;
    use HasLatLng;
    use Reportable;
    use SeederModel;

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'enemy_pack_id',
        'enemy_patrol_id',
        'npc_id',
        'mdt_id',
        'mdt_npc_id',
        'exclusive_enemy_id',
        'mdt_scale',
        'mdt_x',
        'mdt_y',
        'seasonal_index',
        'seasonal_type',
        'teeming',
        'faction',
        'required',
        'skippable',
        'hyper_respawn',
        'kill_priority',
        'enemy_forces_override',
        'enemy_forces_override_teeming',
        'dungeon_difficulty',
        'lat',
        'lng',
    ];

    //    public    $appends    = ['active_auras'];
    public $with = [
        'npc',
        //        'enemyActiveAuras'
    ];

    public $hidden = [
        'mdt_scale',
        'mdt_x',
        'mdt_y',
        'mappingVersion',
        'floor',
        'laravel_through_key',
    ];

    public $timestamps = false;

    protected $casts = [
        'id'                 => 'integer',
        'mapping_version_id' => 'integer',
        'floor_id'           => 'integer',
        'npc_id'             => 'integer',
        'mdt_id'             => 'integer',
        'mdt_npc_id'         => 'integer',
        'exclusive_enemy_id' => 'integer',
        'mdt_scale'          => 'double',
        'enemy_pack_id'      => 'integer',
        'enemy_patrol_id'    => 'integer',
        'required'           => 'integer',
        'skippable'          => 'integer',
        'hyper_respawn'      => 'integer',
        'lat'                => 'double',
        'lng'                => 'double',
        'kill_priority'      => 'integer',
    ];

    public const SEASONAL_TYPE_BEGUILING           = 'beguiling';
    public const SEASONAL_TYPE_AWAKENED            = 'awakened';
    public const SEASONAL_TYPE_INSPIRING           = 'inspiring';
    public const SEASONAL_TYPE_PRIDEFUL            = 'prideful';
    public const SEASONAL_TYPE_TORMENTED           = 'tormented';
    public const SEASONAL_TYPE_ENCRYPTED           = 'encrypted';
    public const SEASONAL_TYPE_MDT_PLACEHOLDER     = 'mdt_placeholder';
    public const SEASONAL_TYPE_SHROUDED            = 'shrouded';
    public const SEASONAL_TYPE_SHROUDED_ZUL_GAMUX  = 'shrouded_zul_gamux';
    public const SEASONAL_TYPE_NO_SHROUDED         = 'no_shrouded';
    public const SEASONAL_TYPE_REQUIRES_ACTIVATION = 'requires_activation';

    public const SEASONAL_TYPE_ALL = [
        self::SEASONAL_TYPE_BEGUILING,
        self::SEASONAL_TYPE_AWAKENED,
        self::SEASONAL_TYPE_INSPIRING,
        self::SEASONAL_TYPE_PRIDEFUL,
        self::SEASONAL_TYPE_TORMENTED,
        self::SEASONAL_TYPE_ENCRYPTED,
        self::SEASONAL_TYPE_MDT_PLACEHOLDER,
        self::SEASONAL_TYPE_SHROUDED,
        self::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX,
        self::SEASONAL_TYPE_NO_SHROUDED,
        self::SEASONAL_TYPE_REQUIRES_ACTIVATION
    ];

    public const TEEMING_VISIBLE = 'visible';
    public const TEEMING_HIDDEN  = 'hidden';

    public const TEEMING_ALL = [
        self::TEEMING_VISIBLE,
        self::TEEMING_HIDDEN,
    ];

    public function getActiveAurasAttribute(): array
    {
        $result = [];

        // Temporarily disabled to improve performance - not using this anyway
        //        foreach ($this->enemyActiveAuras as $activeaura) {
        //            $result[] = $activeaura->spell_id;
        //        }

        return $result;
    }

    public function getMdtNpcId(): int
    {
        return $this->mdt_npc_id ?? $this->npc_id;
    }

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function enemyPack(): BelongsTo
    {
        return $this->belongsTo(EnemyPack::class);
    }

    public function enemyPatrol(): BelongsTo
    {
        return $this->belongsTo(EnemyPatrol::class);
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function enemyActiveAuras(): HasMany
    {
        return $this->hasMany(EnemyActiveAura::class);
    }

    public function exclusiveEnemy(): BelongsTo
    {
        return $this->belongsTo(Enemy::class, 'exclusive_enemy_id');
    }

    public function getDungeonId(): ?int
    {
        return $this->floor?->dungeon_id ?? null;
    }

    public function getUniqueKey(): string
    {
        return sprintf('%d-%d', $this->getMdtNpcId(), $this->mdt_id);
    }
}
