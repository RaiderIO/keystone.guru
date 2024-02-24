<?php

namespace App\Logic\CombatLog\CombatEvents\GenericData\Versions\All;

use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataInterface;
use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use App\Logic\CombatLog\Guid\Guid;

class GenericDataAll implements GenericDataInterface
{
    use ValidatesParameterCount;

    private ?Guid $sourceGuid = null;

    private string $sourceName;

    private string $sourceFlags;

    private string $sourceRaidFlags;

    private ?Guid $destGuid = null;

    private string $destName;

    private string $destFlags;

    private string $destRaidFlags;

    /**
     * @return Guid|null
     */
    public function getSourceGuid(): ?Guid
    {
        return $this->sourceGuid;
    }

    /**
     * @return string
     */
    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    /**
     * @return string
     */
    public function getSourceFlags(): string
    {
        return $this->sourceFlags;
    }

    /**
     * @return string
     */
    public function getSourceRaidFlags(): string
    {
        return $this->sourceRaidFlags;
    }

    /**
     * @return Guid|null
     */
    public function getDestGuid(): ?Guid
    {
        return $this->destGuid;
    }

    /**
     * @return string
     */
    public function getDestName(): string
    {
        return $this->destName;
    }

    /**
     * @return string
     */
    public function getDestFlags(): string
    {
        return $this->destFlags;
    }

    /**
     * @return string
     */
    public function getDestRaidFlags(): string
    {
        return $this->destRaidFlags;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        $this->validateParameters($parameters);

        $this->sourceGuid      = Guid::createFromGuidString($parameters[0]);
        $this->sourceName      = $parameters[1];
        $this->sourceFlags     = $parameters[2];
        $this->sourceRaidFlags = $parameters[3];
        $this->destGuid        = Guid::createFromGuidString($parameters[4]);
        $this->destName        = $parameters[5];
        $this->destFlags       = $parameters[6];
        $this->destRaidFlags   = $parameters[7];

        return $this;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 8;
    }
}
