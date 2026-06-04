<?php

namespace App\Logic\CombatLog\SpecialEvents;

use App\Logic\CombatLog\SpecialEvents\Interfaces\HasCombatLogDungeonContextInterface;
use App\Logic\CombatLog\SpecialEvents\Interfaces\HasCombatLogVersionInterface;
use App\Logic\CombatLog\SpecialEvents\Traits\ComputesVersionLong;
use Override;

/**
 * Trash variant (31 params):
 * RIO_LOG_VERSION,1,SEGMENT_TYPE,mplus_trash,...,DUNGEON_ID,558,SEGMENT_ID,1,CORRELATION_ID,...,
 * CHALLENGE_MODE_STARTED_AT,1780258447159,TYPE,trash,...,COMBAT_LOG_VERSION,22,...,PROJECT_ID,1
 *
 * Boss variant (35 params, adds ENCOUNTER_ID and ENCOUNTER_STARTED_AT):
 * RIO_LOG_VERSION,1,SEGMENT_TYPE,mplus_boss,...,DUNGEON_ID,558,ENCOUNTER_ID,3071,SEGMENT_ID,2,CORRELATION_ID,...,
 * CHALLENGE_MODE_STARTED_AT,1780258447159,ENCOUNTER_STARTED_AT,1780258632834,TYPE,boss,...,PROJECT_ID,1
 *
 * @author Wouter
 *
 * @since 01/06/2026
 */
class RioLogVersion extends SpecialEvent implements HasCombatLogVersionInterface, HasCombatLogDungeonContextInterface
{
    use ComputesVersionLong;

    private int $rioLogVersion;

    private string $segmentType;

    private string $appVersion;

    private int $processorVersion;

    private string $platform;

    private int $instanceID;

    private int $dungeonID;

    private ?int $encounterID;

    private int $segmentID;

    private string $correlationID;

    private int $challengeModeStartedAt;

    private ?int $encounterStartedAt;

    private string $type;

    private string $clientSessionID;

    private int $embeddedCombatLogVersion;

    private bool $advancedLogEnabled;

    private string $buildVersion;

    private int $projectID;

    public function getRioLogVersion(): int
    {
        return $this->rioLogVersion;
    }

    public function getSegmentType(): string
    {
        return $this->segmentType;
    }

    public function getAppVersion(): string
    {
        return $this->appVersion;
    }

    public function getProcessorVersion(): int
    {
        return $this->processorVersion;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getInstanceID(): int
    {
        return $this->instanceID;
    }

    public function getDungeonID(): int
    {
        return $this->dungeonID;
    }

    public function getEncounterID(): ?int
    {
        return $this->encounterID;
    }

    public function getSegmentID(): int
    {
        return $this->segmentID;
    }

    public function getCorrelationID(): string
    {
        return $this->correlationID;
    }

    /**
     * Unix timestamp in milliseconds of when the challenge mode started.
     */
    public function getChallengeModeStartedAt(): int
    {
        return $this->challengeModeStartedAt;
    }

    /**
     * Unix timestamp in milliseconds of when the encounter started. Only present for boss segments.
     */
    public function getEncounterStartedAt(): ?int
    {
        return $this->encounterStartedAt;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getClientSessionID(): string
    {
        return $this->clientSessionID;
    }

    public function getEmbeddedCombatLogVersion(): int
    {
        return $this->embeddedCombatLogVersion;
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

    protected function getVersionNumber(): int
    {
        return $this->embeddedCombatLogVersion;
    }

    public function getChallengeModeID(): int
    {
        return $this->dungeonID;
    }

    public function getKeyLevel(): ?int
    {
        return null;
    }

    /**
     * @return int[]|null
     */
    public function getAffixIDs(): ?array
    {
        return null;
    }

    #[Override]
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $keyValuePairs = [];
        for ($i = 1; $i < count($parameters); $i += 2) {
            $keyValuePairs[$parameters[$i]] = $parameters[$i + 1];
        }

        $this->rioLogVersion            = (int)$parameters[0];
        $this->segmentType              = $keyValuePairs['SEGMENT_TYPE'];
        $this->appVersion               = $keyValuePairs['APP_VERSION'];
        $this->processorVersion         = (int)$keyValuePairs['PROCESSOR_VERSION'];
        $this->platform                 = $keyValuePairs['PLATFORM'];
        $this->instanceID               = (int)$keyValuePairs['INSTANCE_ID'];
        $this->dungeonID                = (int)$keyValuePairs['DUNGEON_ID'];
        $this->encounterID              = isset($keyValuePairs['ENCOUNTER_ID']) ? (int)$keyValuePairs['ENCOUNTER_ID'] : null;
        $this->segmentID                = (int)$keyValuePairs['SEGMENT_ID'];
        $this->correlationID            = $keyValuePairs['CORRELATION_ID'];
        $this->challengeModeStartedAt   = (int)$keyValuePairs['CHALLENGE_MODE_STARTED_AT'];
        $this->encounterStartedAt       = isset($keyValuePairs['ENCOUNTER_STARTED_AT']) ? (int)$keyValuePairs['ENCOUNTER_STARTED_AT'] : null;
        $this->type                     = $keyValuePairs['TYPE'];
        $this->clientSessionID          = $keyValuePairs['CLIENT_SESSION_ID'];
        $this->embeddedCombatLogVersion = (int)$keyValuePairs['COMBAT_LOG_VERSION'];
        $this->advancedLogEnabled       = (bool)$keyValuePairs['ADVANCED_LOG_ENABLED'];
        $this->buildVersion             = $keyValuePairs['BUILD_VERSION'];
        $this->projectID                = (int)$keyValuePairs['PROJECT_ID'];

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 4;
    }

    public function getParameterCount(): int
    {
        return 35;
    }
}
