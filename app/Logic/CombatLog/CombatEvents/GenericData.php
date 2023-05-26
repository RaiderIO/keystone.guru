<?php

namespace App\Logic\CombatLog\CombatEvents;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use InvalidArgumentException;

class GenericData implements HasParameters
{

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
        if (($parameterCount = count($parameters)) !== $this->getParameterCount()) {
            throw new InvalidArgumentException(sprintf('Invalid parameter count - wanted %d, got %d', $this->getParameterCount(), $parameterCount));
        }

        $this->sourceGUID      = $parameters[1];
        $this->sourceName      = $parameters[2];
        $this->sourceFlags     = $parameters[3];
        $this->sourceRaidFlags = $parameters[4];
        $this->destGUID        = $parameters[5];
        $this->destName        = $parameters[6];
        $this->destFlags       = $parameters[7];
        $this->destRaidFlags   = $parameters[8];

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
