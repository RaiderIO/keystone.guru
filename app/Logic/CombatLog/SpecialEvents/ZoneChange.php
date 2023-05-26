<?php

namespace App\Logic\CombatLog\SpecialEvents;

class ZoneChange extends SpecialEvent
{
    private int $zoneId;

    private string $zoneName;

    private string $difficultyId;

    /**
     * @return int
     */
    public function getZoneId(): int
    {
        return $this->zoneId;
    }

    /**
     * @return string
     */
    public function getZoneName(): string
    {
        return $this->zoneName;
    }

    /**
     * @return string
     */
    public function getDifficultyId(): string
    {
        return $this->difficultyId;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        $this->zoneId       = $parameters[0];
        $this->zoneName     = $parameters[1];
        $this->difficultyId = $parameters[2];

        return $this;
    }
}
