<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * CHALLENGE_MODE_END,1841,1,2,1423854,42.612125,63.918190
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class ChallengeModeEnd extends SpecialEvent
{
    private int $instanceId;

    private int $success;

    private int $keystoneLevel;

    private int $totalTimeMS;

    /** @var string ex. 42.612125 */
    private string $unknown1;

    /** @var string ex. 63.918190 */
    private string $unknown2;

    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function getKeystoneLevel(): int
    {
        return $this->keystoneLevel;
    }

    public function getTotalTimeMS(): int
    {
        return $this->totalTimeMS;
    }

    public function getUnknown1(): string
    {
        return $this->unknown1;
    }

    public function getUnknown2(): string
    {
        return $this->unknown2;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->instanceId    = $parameters[0];
        $this->success       = $parameters[1];
        $this->keystoneLevel = $parameters[2];
        $this->totalTimeMS   = $parameters[3];
        $this->unknown1      = $parameters[4];
        $this->unknown2      = $parameters[5];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 6;
    }
}
