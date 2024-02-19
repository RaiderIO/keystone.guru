<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int              $id
 * @property string           $name
 * @property string           $key
 * @property string           $color
 *
 * @property Collection|Npc[] $npcs
 *
 * @mixin Eloquent
 */
class NpcClassification extends CacheModel
{
    use SeederModel;

    public    $hidden   = ['created_at', 'updated_at'];
    protected $fillable = ['id', 'name', 'key', 'color'];

    const NPC_CLASSIFICATION_NORMAL     = 'normal';
    const NPC_CLASSIFICATION_ELITE      = 'elite';
    const NPC_CLASSIFICATION_BOSS       = 'boss';
    const NPC_CLASSIFICATION_FINAL_BOSS = 'finalboss';
    const NPC_CLASSIFICATION_RARE       = 'rare';

    const ALL = [
        self::NPC_CLASSIFICATION_NORMAL     => 1,
        self::NPC_CLASSIFICATION_ELITE      => 2,
        self::NPC_CLASSIFICATION_BOSS       => 3,
        self::NPC_CLASSIFICATION_FINAL_BOSS => 4,
        self::NPC_CLASSIFICATION_RARE       => 5,
    ];

    /**
     * Gets all derived NPCs from this classification.
     *
     * @return HasMany
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }
}
