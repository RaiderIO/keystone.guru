<?php

namespace App\Logic\CombatLog\CombatEvents;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;

class CombatLogEvent extends BaseEvent
{
    protected GenericData $baseEvent;

    protected Prefix $prefix;

    protected Suffix $suffix;

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): CombatLogEvent
    {
        $this->baseEvent = (new GenericData());
        $this->baseEvent->setParameters(array_slice($parameters, 0, $this->baseEvent->getParameterCount()));

        $this->prefix = Prefix::createFromEventName($this->getEventName());
        $this->prefix->setParameters(array_slice($parameters, $this->baseEvent->getParameterCount(), $this->prefix->getParameterCount()));

        $this->suffix = Suffix::createFromEventName($this->getEventName());
        $this->suffix->setParameters(
            array_slice($parameters, $this->baseEvent->getParameterCount() + $this->prefix->getParameterCount())
        );

        return $this;
    }

    /**
     * @return GenericData
     */
    public function getBaseEvent(): GenericData
    {
        return $this->baseEvent;
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
}
