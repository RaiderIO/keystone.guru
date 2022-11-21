<?php

namespace App\Models;

use App\Models\Mapping\CloneForNewMappingVersionNoRelations;
use App\Models\Mapping\MappingModelCloneableInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Traits\Reportable;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $mapping_version_id
 * @property int|null $enemy_pack_id
 * @property int|null $enemy_patrol_id
 * @property int|null $npc_id
 * @property int $floor_id
 * @property int|null $mdt_id The ID in MDT (clone index) that this enemy is coupled to
 * @property int|null $mdt_npc_id The ID of the NPC in MDT that this enemy is coupled to. Usually this will be the same - but MDT sometimes makes mistakes which will require a different NPC to be coupled.
 * @property string $seasonal_type The type of of seasonal effect this enemy has. Awakened to signify an Awakened enemy, Inspiring to signify an Inspiring enemy
 * @property int $seasonal_index Shows/hides this enemy based on the seasonal index as defined in Affix Group. If they match, the enemy is shown, otherwise hidden. If not set enemy is always shown.
 * @property int $mdt_npc_index The index of the NPC in MDT (not saved in DB)
 * @property int $enemy_id Only used for temp MDT enemies (not saved in DB)
 * @property bool $is_mdt Only used for temp MDT enemies (not saved in DB)
 * @property string $teeming
 * @property string $faction
 * @property boolean $required
 * @property boolean $skippable
 * @property int|null $enemy_forces_override
 * @property int|null $enemy_forces_override_teeming
 * @property double $lat
 * @property double $lng
 *
 * @property EnemyPack|null $enemypack
 * @property Npc|null $npc
 * @property Floor $floor
 * @property EnemyPatrol|null $enemypatrol
 * @property MappingVersion $mappingVersion
 *
 * @property EnemyActiveAura[]|Collection $enemyactiveauras
 *
 * @mixin Eloquent
 */
class Enemy extends CacheModel implements MappingModelInterface, MappingModelCloneableInterface
{
    use CloneForNewMappingVersionNoRelations;
    use Reportable;

    protected $fillable = [
        'id',
        'mapping_version_id',
        'floor_id',
        'enemy_pack_id',
        'enemy_patrol_id',
        'npc_id',
        'mdt_id',
        'mdt_npc_id',
        'seasonal_index',
        'seasonal_type',
        'teeming',
        'faction',
        'required',
        'skippable',
        'enemy_forces_override',
        'enemy_forces_override_teeming',
        'lat',
        'lng',
    ];
    public $appends = ['active_auras'];
    public $with = ['npc', 'enemyactiveauras'];
    public $hidden = ['laravel_through_key'];
    public $timestamps = false;
    protected $casts = [
        'mapping_version_id' => 'integer',
        'floor_id'           => 'integer',
        'enemy_pack_id'      => 'integer',
        'enemy_patrol_id'    => 'integer',
        'lat'                => 'double',
        'lng'                => 'double',
    ];

    const SEASONAL_TYPE_AWAKENED           = 'awakened';
    const SEASONAL_TYPE_INSPIRING          = 'inspiring';
    const SEASONAL_TYPE_PRIDEFUL           = 'prideful';
    const SEASONAL_TYPE_TORMENTED          = 'tormented';
    const SEASONAL_TYPE_ENCRYPTED          = 'encrypted';
    const SEASONAL_TYPE_MDT_PLACEHOLDER    = 'mdt_placeholder';
    const SEASONAL_TYPE_SHROUDED           = 'shrouded';
    const SEASONAL_TYPE_SHROUDED_ZUL_GAMUX = 'shrouded_zul_gamux';
    const SEASONAL_TYPE_NO_SHROUDED        = 'no_shrouded';

    const SEASONAL_TYPE_ALL = [
        self::SEASONAL_TYPE_AWAKENED,
        self::SEASONAL_TYPE_INSPIRING,
        self::SEASONAL_TYPE_PRIDEFUL,
        self::SEASONAL_TYPE_TORMENTED,
        self::SEASONAL_TYPE_ENCRYPTED,
        self::SEASONAL_TYPE_MDT_PLACEHOLDER,
        self::SEASONAL_TYPE_SHROUDED,
        self::SEASONAL_TYPE_SHROUDED_ZUL_GAMUX,
        self::SEASONAL_TYPE_NO_SHROUDED,
    ];

    const TEEMING_VISIBLE = 'visible';
    const TEEMING_HIDDEN  = 'hidden';

    const TEEMING_ALL = [
        self::TEEMING_VISIBLE,
        self::TEEMING_HIDDEN,
    ];

    /**
     * @return array
     */
    public function getActiveAurasAttribute(): array
    {
        $result = [];

        foreach ($this->enemyactiveauras as $activeaura) {
            $result[] = $activeaura->spell_id;
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getMdtNpcId(): int
    {
        return $this->mdt_npc_id ?? $this->npc_id;
    }

    /**
     * @return BelongsTo
     */
    public function enemypack(): BelongsTo
    {
        return $this->belongsTo(EnemyPack::class, 'enemy_pack_id');
    }

    /**
     * @return BelongsTo
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return BelongsTo
     */
    public function enemypatrol(): BelongsTo
    {
        return $this->belongsTo(EnemyPatrol::class, 'enemy_patrol_id');
    }

    /**
     * @return BelongsTo
     */
    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    /**
     * @return HasMany
     */
    public function enemyactiveauras(): HasMany
    {
        return $this->hasMany(EnemyActiveAura::class);
    }

    /**
     * @return int
     */
    public function getDungeonId(): int
    {
        return $this->floor->dungeon_id;
    }
}
