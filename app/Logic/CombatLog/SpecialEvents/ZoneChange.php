<?php

namespace App\Logic\CombatLog\SpecialEvents;

class ZoneChange extends SpecialEvent
{
    private int $zoneId;

    private string $zoneName;

    private string $difficultyId;

    public function getZoneId(): int
    {
        return $this->zoneId;
    }

    public function getZoneName(): string
    {
        return $this->zoneName;
    }

    public function getDifficultyId(): string
    {
        return $this->difficultyId;
    }

    #[\Override]
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->zoneId       = $parameters[0];
        $this->zoneName     = $parameters[1];
        $this->difficultyId = $parameters[2];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 3;
    }
}
