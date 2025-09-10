<?php

namespace App\Logic\CombatLog\CombatEvents;

use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataBuilder;
use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataBuilder;
use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use Exception;

class AdvancedCombatLogEvent extends CombatLogEvent
{
    private ?AdvancedDataInterface $advancedData = null;

    /**
     * @throws Exception
     */
    public function setParameters(array $parameters): CombatLogEvent
    {
        $this->genericData = GenericDataBuilder::create($this->getCombatLogVersion());
        $this->genericData->setParameters(array_slice($parameters, 0, $this->genericData->getParameterCount()));

        $this->prefix = Prefix::createFromEventName($this->getCombatLogVersion(), $this->getEventName());
        $this->prefix->setParameters(array_slice($parameters, $this->genericData->getParameterCount(), $this->prefix->getParameterCount()));

        $this->advancedData = AdvancedDataBuilder::create($this->getCombatLogVersion());
        $this->advancedData->setParameters(
            array_slice($parameters, $this->genericData->getParameterCount() + $this->prefix->getParameterCount(), $this->advancedData->getParameterCount()),
        );

        $this->suffix = Suffix::createFromEventName($this->getCombatLogVersion(), $this->getEventName());
        $this->suffix->setParameters(
            array_slice($parameters, $this->genericData->getParameterCount() + $this->prefix->getParameterCount() + $this->advancedData->getParameterCount()),
        );

        return $this;
    }

    public function getAdvancedData(): AdvancedDataInterface
    {
        return $this->advancedData;
    }
}
