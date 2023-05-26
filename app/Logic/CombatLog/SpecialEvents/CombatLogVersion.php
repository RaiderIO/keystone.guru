<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,10.1.0,PROJECT_ID,1
 *
 * @package App\Logic\CombatLog\SpecialEvents
 * @author Wouter
 * @since 26/05/2023
 */
class CombatLogVersion extends SpecialEvent
{
    private int $version;

    private int $advancedLogEnabled;

    private string $buildVersion;

    private int $projectID;

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getAdvancedLogEnabled(): int
    {
        return $this->advancedLogEnabled;
    }

    /**
     * @return string
     */
    public function getBuildVersion(): string
    {
        return $this->buildVersion;
    }

    /**
     * @return int
     */
    public function getProjectID(): int
    {
        return $this->projectID;
    }



    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        $this->version            = $parameters[0];
        $this->advancedLogEnabled = $parameters[2];
        $this->buildVersion       = $parameters[4];
        $this->projectID          = $parameters[6];

        return $this;
    }
}
