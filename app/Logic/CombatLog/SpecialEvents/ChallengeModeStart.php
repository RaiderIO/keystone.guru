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

    private array $affixIDs = [];

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
     * @return array
     */
    public function getAffixIDs(): array
    {
        return $this->affixIDs;
    }


    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->zoneName        = $parameters[0];
        $this->instanceID      = $parameters[1];
        $this->challengeModeID = $parameters[2];
        $this->keystoneLevel   = $parameters[3];


        $affixIds = array_slice($parameters, 4);
        foreach ($affixIds as $affixId) {
            $this->affixIDs[] = (int)str_replace(['[', ']'], '', (string) $affixId);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getOptionalParameterCount(): int
    {
        return 3;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 8;
    }
}
