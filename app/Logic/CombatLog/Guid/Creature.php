<?php

namespace App\Logic\CombatLog\Guid;

class Creature extends Guid
{
    public const CREATURE_UNIT_TYPE_CREATURE    = 'Creature';
    public const CREATURE_UNIT_TYPE_PET         = 'Pet';
    public const CREATURE_UNIT_TYPE_GAME_OBJECT = 'GameObject';
    public const CREATURE_UNIT_TYPE_VEHICLE     = 'Vehicle';

    private readonly string $unitType;

    private readonly int $unknown1;

    private readonly int $serverId;

    private readonly int $instanceId;

    private readonly int $zoneUID;

    private readonly int $id;

    private readonly string $spawnUID;

    protected function __construct(string $guid, array $parameters)
    {
        parent::__construct($guid);

        $this->unitType   = $parameters[0];
        $this->unknown1   = $parameters[1];
        $this->serverId   = $parameters[2];
        $this->instanceId = $parameters[3];
        $this->zoneUID    = $parameters[4];
        $this->id         = $parameters[5];
        $this->spawnUID   = $parameters[6];

        // Creature-0-3778-2526-12117-196045-0003CA41E4
    }

    public function getUnitType(): string
    {
        return $this->unitType;
    }

    public function getUnknown1(): int
    {
        return $this->unknown1;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    public function getZoneUID(): int
    {
        return $this->zoneUID;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSpawnUID(): string
    {
        return $this->spawnUID;
    }
}
