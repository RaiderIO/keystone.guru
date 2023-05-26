<?php

namespace App\Logic\CombatLog\CombatEvents;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Traits\ValidatesParameterCount;
use InvalidArgumentException;

class GenericData implements HasParameters
{
    use ValidatesParameterCount;

    private string $sourceGUID;

    private string $sourceName;

    private string $sourceFlags;

    private string $sourceRaidFlags;

    private string $destGUID;

    private string $destName;

    private string $destFlags;

    private string $destRaidFlags;

    /**
     * @return string
     */
    public function getSourceGUID(): string
    {
        return $this->sourceGUID;
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
     * @return string
     */
    public function getDestGUID(): string
    {
        return $this->destGUID;
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

        $this->sourceGUID      = $parameters[0];
        $this->sourceName      = $parameters[1];
        $this->sourceFlags     = $parameters[2];
        $this->sourceRaidFlags = $parameters[3];
        $this->destGUID        = $parameters[4];
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
