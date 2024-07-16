<?php

namespace App\Models;

use App\Models\Npc\Npc;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int              $id
 * @property string           $name
 * @property string           $key
 *
 * @property Collection|Npc[] $npcs
 *
 * @mixin Eloquent
 */
class Characteristic extends CacheModel
{
    use SeederModel;

    protected $fillable = ['id', 'name', 'key'];

    public const CHARACTERISTIC_TAUNT        = 'taunt';
    public const CHARACTERISTIC_INCAPACITATE = 'incapacitate';
    public const CHARACTERISTIC_SILENCE      = 'silence';
    public const CHARACTERISTIC_KNOCK        = 'knock';
    public const CHARACTERISTIC_GRIP         = 'grip';
    public const CHARACTERISTIC_MIND_CONTROL = 'mind_control';
    public const CHARACTERISTIC_ROOT         = 'root';
    public const CHARACTERISTIC_FEAR         = 'fear';
    public const CHARACTERISTIC_BANISH       = 'banish';
    public const CHARACTERISTIC_DISORIENT    = 'disorient';
    public const CHARACTERISTIC_STUN         = 'stun';
    public const CHARACTERISTIC_SLOW         = 'slow';
    public const CHARACTERISTIC_SLEEP_WALK   = 'sleep_walk';

    public const ALL = [
        self::CHARACTERISTIC_TAUNT        => 1,
        self::CHARACTERISTIC_INCAPACITATE => 2,
        self::CHARACTERISTIC_SILENCE      => 3,
        self::CHARACTERISTIC_KNOCK        => 4,
        self::CHARACTERISTIC_GRIP         => 5,
        self::CHARACTERISTIC_MIND_CONTROL => 6,
        self::CHARACTERISTIC_ROOT         => 7,
        self::CHARACTERISTIC_FEAR         => 8,
        self::CHARACTERISTIC_BANISH       => 9,
        self::CHARACTERISTIC_DISORIENT    => 10,
        self::CHARACTERISTIC_STUN         => 11,
        self::CHARACTERISTIC_SLOW         => 12,
        self::CHARACTERISTIC_SLEEP_WALK   => 13,
    ];

    /**
     * Gets all derived NPCs from this classification.
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }
}
