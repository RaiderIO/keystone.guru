<?php

namespace App\Logic\CombatLog\SpecialEvents;

class CombatantInfo extends SpecialEvent
{
    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {

        parent::setParameters($parameters);


        return $this;
    }

    /**
     * @return int
     */
    public function getOptionalParameterCount(): int
    {
        // This event has a lot of variables because it uses an incorrect delimiter to escape the contents ( "(" and ")" )
        return 1000;
    }


    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 1000;
    }
}
