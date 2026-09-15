<?php

namespace App\Logic\CombatLog\CombatEvents\GenericData\Versions\All;

use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataInterface;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use App\Logic\CombatLog\Guid\Guid;

/**
 * The name/flag fields are cheap array reads and stay eagerly assigned in {@see setParameters()}; only the
 * two GUIDs are parsed lazily, each on its own first getter access.
 */
class GenericDataAll implements GenericDataInterface
{
    use ValidatesParameterCount;

    private string $sourceGuidRaw = '0000000000000000';

    /** false = not parsed yet ({@see Guid::createFromGuidString()} may legitimately return null) */
    private Guid|null|false $sourceGuid = false;

    private string $sourceName;

    private string $sourceFlags;

    private string $sourceRaidFlags;

    private string $destGuidRaw = '0000000000000000';

    /** false = not parsed yet ({@see Guid::createFromGuidString()} may legitimately return null) */
    private Guid|null|false $destGuid = false;

    private string $destName;

    private string $destFlags;

    private string $destRaidFlags;

    public function getSourceGuidRaw(): string
    {
        return $this->sourceGuidRaw;
    }

    public function getSourceGuid(): ?Guid
    {
        if ($this->sourceGuid === false) {
            $this->sourceGuid = Guid::createFromGuidString($this->sourceGuidRaw);
        }

        return $this->sourceGuid;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function getSourceFlags(): string
    {
        return $this->sourceFlags;
    }

    public function getSourceRaidFlags(): string
    {
        return $this->sourceRaidFlags;
    }

    public function getDestGuidRaw(): string
    {
        return $this->destGuidRaw;
    }

    public function getDestGuid(): ?Guid
    {
        if ($this->destGuid === false) {
            $this->destGuid = Guid::createFromGuidString($this->destGuidRaw);
        }

        return $this->destGuid;
    }

    public function getDestName(): string
    {
        return $this->destName;
    }

    public function getDestFlags(): string
    {
        return $this->destFlags;
    }

    public function getDestRaidFlags(): string
    {
        return $this->destRaidFlags;
    }

    public function setParameters(array $parameters): HasParameters
    {
        $this->validateParameters($parameters);

        $this->sourceGuidRaw   = (string)$parameters[0];
        $this->sourceName      = $parameters[1];
        $this->sourceFlags     = $parameters[2];
        $this->sourceRaidFlags = $parameters[3];
        $this->destGuidRaw     = (string)$parameters[4];
        $this->destName        = $parameters[5];
        $this->destFlags       = $parameters[6];
        $this->destRaidFlags   = $parameters[7];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 8;
    }
}
