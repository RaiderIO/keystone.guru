<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced;

use App\Logic\CombatLog\CombatEvents\GenericData;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;

class AdvancedCombatLogEvent extends CombatLogEvent
{

    private ?AdvancedData $advancedData = null;

    /**
     * @param array $parameters
     * @return CombatLogEvent
     */
    public function setParameters(array $parameters): CombatLogEvent
    {
        $this->baseEvent = new GenericData();
        $this->baseEvent->setParameters(array_slice($parameters, 0, $this->baseEvent->getParameterCount()));

        $this->prefix = Prefix::createFromEventName($this->getEventName());
        $this->prefix->setParameters(array_slice($parameters, $this->baseEvent->getParameterCount(), $this->prefix->getParameterCount()));

        $this->advancedData = new AdvancedData();
        $this->advancedData->setParameters(
            array_slice($parameters, $this->baseEvent->getParameterCount() + $this->prefix->getParameterCount(), $this->advancedData->getParameterCount())
        );

        $this->suffix = Suffix::createFromEventName($this->getEventName());
        $this->suffix->setParameters(
            array_slice($parameters, $this->baseEvent->getParameterCount() + $this->prefix->getParameterCount() + $this->advancedData->getParameterCount())
        );

        return $this;
    }


    /**
     * @return AdvancedData
     */
    public function getAdvancedData(): AdvancedData
    {
        return $this->advancedData;
    }
}
