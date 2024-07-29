<?php

namespace App\Models\Npc;

use App\Models\CacheModel;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int             $id
 * @property string          $name
 * @property string          $key
 * @property string          $color
 *
 * @property Collection<Npc> $npcs
 *
 * @mixin Eloquent
 */
class NpcClassification extends CacheModel
{
    use SeederModel;

    protected $fillable = ['id', 'name', 'key', 'color'];

    public const NPC_CLASSIFICATION_NORMAL     = 'normal';
    public const NPC_CLASSIFICATION_ELITE      = 'elite';
    public const NPC_CLASSIFICATION_BOSS       = 'boss';
    public const NPC_CLASSIFICATION_FINAL_BOSS = 'finalboss';
    public const NPC_CLASSIFICATION_RARE       = 'rare';

    public const ALL = [
        self::NPC_CLASSIFICATION_NORMAL     => 1,
        self::NPC_CLASSIFICATION_ELITE      => 2,
        self::NPC_CLASSIFICATION_BOSS       => 3,
        self::NPC_CLASSIFICATION_FINAL_BOSS => 4,
        self::NPC_CLASSIFICATION_RARE       => 5,
    ];

    /**
     * Gets all derived NPCs from this classification.
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }
}
