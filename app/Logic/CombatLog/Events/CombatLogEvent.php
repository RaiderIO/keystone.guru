<?php

namespace App\Logic\CombatLog\Events;

use App\Logic\CombatLog\Events\Interfaces\HasParameters;
use App\Logic\CombatLog\Events\Prefixes\Prefix;
use App\Logic\CombatLog\Events\Suffixes\Suffix;
use Illuminate\Support\Carbon;

class CombatLogEvent implements HasParameters
{

    private Carbon $timestamp;

    private string $eventName;

    private string $sourceGUID;

    private string $sourceName;

    private string $sourceFlags;

    private string $sourceRaidFlags;

    private string $destGUID;

    private string $destName;

    private string $destFlags;

    private string $destRaidFlags;

    protected Prefix $prefix;

    protected Suffix $suffix;

    /**
     * @param string $timestamp
     */
    public function __construct(string $timestamp)
    {

    }

    /**
     * @return Carbon
     */
    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

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
     * @return Prefix
     */
    public function getPrefix(): Prefix
    {
        return $this->prefix;
    }

    /**
     * @return Suffix
     */
    public function getSuffix(): Suffix
    {
        return $this->suffix;
    }

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        // Do not call parent
        // parent::setParameters($parameters);

        $this->prefix = Prefix::createFromEventName($this->eventName);
        $this->prefix->setParameters(array_slice($parameters, $this->getParameterCount(), $this->prefix->getParameterCount()));

        $this->suffix = Suffix::createFromEventName($this->eventName);
        $this->suffix->setParameters(
            array_slice($parameters, $this->getParameterCount() + $this->prefix->getParameterCount())
        );

        $this->eventName       = $parameters[0];
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
        return 9;
    }
}
