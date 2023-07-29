<?php

namespace App\Logic\CombatLog\Guid;

class Creature extends Guid
{
    const CREATURE_UNIT_TYPE_CREATURE    = 'Creature';
    const CREATURE_UNIT_TYPE_PET         = 'Pet';
    const CREATURE_UNIT_TYPE_GAME_OBJECT = 'GameObject';
    const CREATURE_UNIT_TYPE_VEHICLE     = 'Vehicle';

    private string $unitType;
    
    private int $unknown1;

    private int $serverId;

    private int $instanceId;

    private int $zoneUID;

    private int $id;

    private string $spawnUID;

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

    /**
     * @return string
     */
    public function getUnitType(): string
    {
        return $this->unitType;
    }
    /**
     * @return int
     */
    public function getUnknown1(): int
    {
        return $this->unknown1;
    }

    /**
     * @return int
     */
    public function getServerId(): int
    {
        return $this->serverId;
    }

    /**
     * @return int
     */
    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    /**
     * @return int
     */
    public function getZoneUID(): int
    {
        return $this->zoneUID;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSpawnUID(): string
    {
        return $this->spawnUID;
    }
}
