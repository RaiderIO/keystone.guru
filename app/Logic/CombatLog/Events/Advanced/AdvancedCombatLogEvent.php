<?php

namespace App\Logic\CombatLog\Events\Advanced;

use App\Logic\CombatLog\Events\CombatLogEvent;
use App\Logic\CombatLog\Events\Interfaces\HasParameters;
use App\Logic\CombatLog\Events\Prefixes\Prefix;
use App\Logic\CombatLog\Events\Suffixes\Suffix;

class AdvancedCombatLogEvent extends CombatLogEvent
{

    private ?AdvancedData $advancedData = null;


    /**
     * @param string $timestamp
     * @param string[] $parameters
     */
    public function __construct(string $timestamp, array $parameters)
    {
        // https://wowpedia.fandom.com/wiki/COMBAT_LOG_EVENT#Advanced_parameters
        // 9 base params, 3 prefix params, 10 suffix params = 22 params max for base logs, if bigger we include the advanced params
        $isAdvanced = count($parameters) > 22;

        $this->setParameters(array_slice($parameters, 0, $this->getParameterCount()));
    }

    /**
     * @param array $parameters
     * @return HasParameters
     */
    public function setParameters(array $parameters): HasParameters
    {
        $this->prefix = Prefix::createFromEventName($this->getEventName());
        $this->prefix->setParameters(array_slice($parameters, $this->getParameterCount(), $this->prefix->getParameterCount()));

        $this->advancedData = new AdvancedData();
        $this->advancedData->setParameters(
            array_slice($parameters, $this->prefix->getParameterCount(), $this->advancedData->getParameterCount())
        );

        $this->suffix = Suffix::createFromEventName($this->getEventName());
        $this->suffix->setParameters(
            array_slice($parameters, $this->getParameterCount() + $this->prefix->getParameterCount() + $this->advancedData->getParameterCount())
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
