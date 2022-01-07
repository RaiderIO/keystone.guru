<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $shortname
 * @property string $color
 *
 * @mixin Eloquent
 */
class NpcClassification extends CacheModel
{
    public $hidden = ['created_at', 'updated_at'];
    protected $fillable = ['id', 'name', 'shortname', 'color'];

    const NPC_CLASSIFICATION_NORMAL     = 'normal';
    const NPC_CLASSIFICATION_ELITE      = 'elite';
    const NPC_CLASSIFICATION_BOSS       = 'boss';
    const NPC_CLASSIFICATION_FINAL_BOSS = 'finalboss';

    const ALL = [
        self::NPC_CLASSIFICATION_NORMAL     => 1,
        self::NPC_CLASSIFICATION_ELITE      => 2,
        self::NPC_CLASSIFICATION_BOSS       => 3,
        self::NPC_CLASSIFICATION_FINAL_BOSS => 4,
    ];

    /**
     * Gets all derived NPCs from this classification.
     *
     * @return HasMany
     */
    function npcs(): HasMany
    {
        return $this->hasMany('App\Models\Npc');
    }
}
