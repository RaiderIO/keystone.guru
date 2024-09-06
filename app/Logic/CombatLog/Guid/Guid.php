<?php

namespace App\Logic\CombatLog\Guid;

use App\Logic\CombatLog\Guid\MissType\Absorb;
use App\Logic\CombatLog\Guid\MissType\Block;
use App\Logic\CombatLog\Guid\MissType\Deflect;
use App\Logic\CombatLog\Guid\MissType\Dodge;
use App\Logic\CombatLog\Guid\MissType\Evade;
use App\Logic\CombatLog\Guid\MissType\Immune;
use App\Logic\CombatLog\Guid\MissType\Miss;
use App\Logic\CombatLog\Guid\MissType\Parry;
use App\Logic\CombatLog\Guid\MissType\Reflect;
use App\Logic\CombatLog\Guid\MissType\Resist;
use Stringable;

abstract class Guid implements Stringable
{
    private const GUID_TYPE_BATTLE_PET    = 'BattlePet';
    private const GUID_TYPE_B_NET_ACCOUNT = 'BNetAccount';
    private const GUID_TYPE_CAST          = 'Cast';
    private const GUID_TYPE_CLIENT_ACTOR  = 'ClientActor';
    private const GUID_TYPE_CREATURE      = 'Creature';
    private const GUID_TYPE_FOLLOWER      = 'Follower';
    private const GUID_TYPE_GAME_OBJECT   = 'GameObject';
    private const GUID_TYPE_ITEM          = 'Item';
    private const GUID_TYPE_PET           = 'Pet';
    private const GUID_TYPE_PLAYER        = 'Player';
    private const GUID_TYPE_VIGNETTE      = 'Vignette';
    private const GUID_TYPE_VEHICLE       = 'Vehicle';

    // This GUID is a bit of a hack because evading enemies have their InfoGUID set to EVADE. This ensures we can still
    // use strongly typed GUIDs while still supporting this
    private const GUID_TYPE_ABSORB  = 'ABSORB';
    private const GUID_TYPE_BLOCK   = 'BLOCK';
    private const GUID_TYPE_DEFLECT = 'DEFLECT';
    private const GUID_TYPE_DODGE   = 'DODGE';
    private const GUID_TYPE_EVADE   = 'EVADE';
    private const GUID_TYPE_IMMUNE  = 'IMMUNE';
    private const GUID_TYPE_MISS    = 'MISS';
    private const GUID_TYPE_PARRY   = 'PARRY';
    private const GUID_TYPE_REFLECT = 'REFLECT';
    private const GUID_TYPE_RESIST  = 'RESIST';


    private const GUID_TYPE_CLASS_MAPPING = [
        self::GUID_TYPE_BATTLE_PET    => BattlePet::class,
        self::GUID_TYPE_B_NET_ACCOUNT => BNetAccount::class,
        self::GUID_TYPE_CAST          => Cast::class,
        self::GUID_TYPE_CLIENT_ACTOR  => ClientActor::class,
        self::GUID_TYPE_CREATURE      => Creature::class,
        self::GUID_TYPE_FOLLOWER      => Follower::class,
        self::GUID_TYPE_GAME_OBJECT   => Creature::class,
        self::GUID_TYPE_ITEM          => Item::class,
        self::GUID_TYPE_PET           => Creature::class,
        self::GUID_TYPE_PLAYER        => Player::class,
        self::GUID_TYPE_VIGNETTE      => Vignette::class,
        self::GUID_TYPE_VEHICLE       => Creature::class,

        self::GUID_TYPE_ABSORB  => Absorb::class,
        self::GUID_TYPE_BLOCK   => Block::class,
        self::GUID_TYPE_DEFLECT => Deflect::class,
        self::GUID_TYPE_DODGE   => Dodge::class,
        self::GUID_TYPE_EVADE   => Evade::class,
        self::GUID_TYPE_IMMUNE  => Immune::class,
        self::GUID_TYPE_MISS    => Miss::class,
        self::GUID_TYPE_PARRY   => Parry::class,
        self::GUID_TYPE_REFLECT => Reflect::class,
        self::GUID_TYPE_RESIST  => Resist::class,
    ];

    protected function __construct(private readonly string $guid)
    {
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public static function createFromGuidString(string $guid): ?Guid
    {
        if ($guid === '0000000000000000') {
            return null;
        }

        $result = null;

        $parameters = explode('-', $guid);
        $guidType   = $parameters[0];

        foreach (self::GUID_TYPE_CLASS_MAPPING as $guidTypeCandidate => $className) {
            if ($guidType === $guidTypeCandidate) {
                $result = new $className($guid, $parameters);
                break;
            }
        }

        return $result;
    }

    public function __toString(): string
    {
        return $this->guid;
    }
}
