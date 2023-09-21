<?php

namespace App\Logic\CombatLog\CombatEvents;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedData;
use App\Logic\CombatLog\CombatEvents\Generic\GenericData;
use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use Exception;

class AdvancedCombatLogEvent extends CombatLogEvent
{
    private ?AdvancedData $advancedData = null;

    /**
     * @param array $parameters
     * @return CombatLogEvent
     * @throws Exception
     */
    public function setParameters(array $parameters): CombatLogEvent
    {
        $this->genericData = new GenericData($this->getCombatLogVersion());
        $this->genericData->setParameters(array_slice($parameters, 0, $this->genericData->getParameterCount()));

        $this->prefix = Prefix::createFromEventName($this->getCombatLogVersion(), $this->getEventName());
        $this->prefix->setParameters(array_slice($parameters, $this->genericData->getParameterCount(), $this->prefix->getParameterCount()));

        $this->advancedData = new AdvancedData($this->getCombatLogVersion());
        $this->advancedData->setParameters(
            array_slice($parameters, $this->genericData->getParameterCount() + $this->prefix->getParameterCount(), $this->advancedData->getParameterCount())
        );

        $this->suffix = Suffix::createFromEventName($this->getCombatLogVersion(), $this->getEventName());
        $this->suffix->setParameters(
            array_slice($parameters, $this->genericData->getParameterCount() + $this->prefix->getParameterCount() + $this->advancedData->getParameterCount())
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
