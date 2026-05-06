<?php

namespace App\Models;

use App\Models\Npc\Npc;
use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int    $id
 * @property string $name
 * @property string $key
 *
 * @property Collection<Npc> $npcs
 *
 * @mixin Eloquent
 */
class Characteristic extends CacheModel
{
    use SeederModel;

    protected $fillable = [
        'id',
        'name',
        'key',
    ];

    protected $visible = [
        'name',
        'key',
    ];

    public const CHARACTERISTIC_TAUNT           = 'taunt';
    public const CHARACTERISTIC_INCAPACITATE    = 'incapacitate';
    public const CHARACTERISTIC_SUBJUGATE_DEMON = 'subjugate_demon';
    public const CHARACTERISTIC_CONTROL_UNDEAD  = 'control_undead';
    public const CHARACTERISTIC_SILENCE         = 'silence';
    public const CHARACTERISTIC_KNOCK           = 'knock';
    public const CHARACTERISTIC_GRIP            = 'grip';
    public const CHARACTERISTIC_SHACKLE_UNDEAD  = 'shackle_undead';
    public const CHARACTERISTIC_MIND_CONTROL    = 'mind_control';
    public const CHARACTERISTIC_POLYMORPH       = 'polymorph';
    public const CHARACTERISTIC_ROOT            = 'root';
    public const CHARACTERISTIC_FEAR            = 'fear';
    public const CHARACTERISTIC_BANISH          = 'banish';
    public const CHARACTERISTIC_DISORIENT       = 'disorient';
    public const CHARACTERISTIC_REPENTANCE      = 'repentance';
    public const CHARACTERISTIC_IMPRISON        = 'imprison';
    public const CHARACTERISTIC_SAP             = 'sap';
    public const CHARACTERISTIC_STUN            = 'stun';
    public const CHARACTERISTIC_SLOW            = 'slow';
    public const CHARACTERISTIC_SLEEP_WALK      = 'sleep_walk';
    public const CHARACTERISTIC_SCARE_BEAST     = 'scare_beast';
    public const CHARACTERISTIC_HIBERNATE       = 'hibernate';
    public const CHARACTERISTIC_TURN_EVIL       = 'turn_evil';
    public const CHARACTERISTIC_MIND_SOOTHE     = 'mind_soothe';

    public const ALL = [
        self::CHARACTERISTIC_TAUNT           => 1,
        self::CHARACTERISTIC_INCAPACITATE    => 2,
        self::CHARACTERISTIC_SUBJUGATE_DEMON => 3,
        self::CHARACTERISTIC_CONTROL_UNDEAD  => 4,
        self::CHARACTERISTIC_SILENCE         => 5,
        self::CHARACTERISTIC_KNOCK           => 6,
        self::CHARACTERISTIC_GRIP            => 7,
        self::CHARACTERISTIC_SHACKLE_UNDEAD  => 8,
        self::CHARACTERISTIC_MIND_CONTROL    => 9,
        self::CHARACTERISTIC_POLYMORPH       => 10,
        self::CHARACTERISTIC_ROOT            => 11,
        self::CHARACTERISTIC_FEAR            => 12,
        self::CHARACTERISTIC_BANISH          => 13,
        self::CHARACTERISTIC_DISORIENT       => 14,
        self::CHARACTERISTIC_REPENTANCE      => 15,
        self::CHARACTERISTIC_IMPRISON        => 16,
        self::CHARACTERISTIC_SAP             => 17,
        self::CHARACTERISTIC_STUN            => 18,
        self::CHARACTERISTIC_SLOW            => 19,
        self::CHARACTERISTIC_SLEEP_WALK      => 20,
        self::CHARACTERISTIC_SCARE_BEAST     => 21,
        self::CHARACTERISTIC_HIBERNATE       => 22,
        self::CHARACTERISTIC_TURN_EVIL       => 23,
        self::CHARACTERISTIC_MIND_SOOTHE     => 24,
    ];

    /**
     * Gets all derived NPCs from this classification.
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }
}
