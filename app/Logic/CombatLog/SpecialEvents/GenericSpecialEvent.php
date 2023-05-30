<?php

namespace App\Logic\CombatLog\SpecialEvents;

use App\Logic\CombatLog\CombatEvents\GenericData;

abstract class GenericSpecialEvent extends SpecialEvent
{
    private GenericData $genericData;


    /**
     * @return GenericData
     */
    public function getGenericData(): GenericData
    {
        return $this->genericData;
    }


    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->genericData = new GenericData();
        $this->genericData->setParameters(array_slice($parameters, 0, $this->genericData->getParameterCount()));

        return $this;
    }
}
