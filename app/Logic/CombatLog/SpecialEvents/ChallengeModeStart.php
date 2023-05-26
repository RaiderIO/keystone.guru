<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * CHALLENGE_MODE_START,"The Underrot",1841,251,2,[9]
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class ChallengeModeStart extends SpecialEvent
{
    private string $zoneName;

    private int $instanceID;

    private int $challengeModeID;

    private int $keystoneLevel;

    private string $affixIDs;

    /**
     * @return string
     */
    public function getZoneName(): string
    {
        return $this->zoneName;
    }

    /**
     * @return int
     */
    public function getInstanceID(): int
    {
        return $this->instanceID;
    }

    /**
     * @return int
     */
    public function getChallengeModeID(): int
    {
        return $this->challengeModeID;
    }

    /**
     * @return int
     */
    public function getKeystoneLevel(): int
    {
        return $this->keystoneLevel;
    }

    /**
     * @return string
     */
    public function getAffixIDs(): string
    {
        return $this->affixIDs;
    }


    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        $this->zoneName        = $parameters[0];
        $this->instanceID      = $parameters[1];
        $this->challengeModeID = $parameters[2];
        $this->keystoneLevel   = $parameters[3];
        $this->affixIDs        = $parameters[4];

        return $this;
    }
}
