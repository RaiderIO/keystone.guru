<?php

namespace App\Logic\CombatLog\SpecialEvents;

use App\Logic\CombatLog\CombatLogVersion as CombatLogVersionConstant;
use Exception;

/**
 * COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,10.1.0,PROJECT_ID,1
 * COMBAT_LOG_VERSION,21,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,11.0.2,PROJECT_ID,1
 *
 * @author Wouter
 *
 * @since 26/05/2023
 */
class CombatLogVersion extends SpecialEvent
{
    private int $version;

    private bool $advancedLogEnabled;

    private string $buildVersion;

    private int $projectID;

    public function getVersion(): int
    {
        return $this->version;
    }

    public function isAdvancedLogEnabled(): bool
    {
        return $this->advancedLogEnabled;
    }

    public function getBuildVersion(): string
    {
        return $this->buildVersion;
    }

    public function getProjectID(): int
    {
        return $this->projectID;
    }

    /**
     * Get the version as a long integer that incorporates the combat log version, major, minor and patch version
     * @return int
     */
    public function getVersionLong(): int
    {
        [$major, $minor, $patch] = explode('.', $this->buildVersion);

        return ($this->version * 1_000_000_000) +
            ((int)$major * 1_000_000) + ((int)($minor ?? 0) * 1_000) + (int)($patch ?? 0);
    }

    /**
     * @throws Exception
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->version            = $parameters[0];
        $this->advancedLogEnabled = $parameters[2];
        $this->buildVersion       = $parameters[4];
        $this->projectID          = $parameters[6];

        if (!isset(CombatLogVersionConstant::ALL[$this->getVersionLong()])) {
            throw new Exception(sprintf('Unable to find combat log version %d!', $this->getVersionLong()));
        }

        return $this;
    }

    public function getParameterCount(): int
    {
        return 7;
    }
}
