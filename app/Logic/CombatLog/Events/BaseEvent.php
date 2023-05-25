<?php

namespace App\Logic\CombatLog\Events;

use App\Logic\CombatLog\Events\Interfaces\HasParameters;
use InvalidArgumentException;

abstract class BaseEvent implements HasParameters
{
    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): HasParameters
    {
        if (($parameterCount = count($parameters)) !== $this->getParameterCount()) {
            throw new InvalidArgumentException(sprintf('Invalid parameter count - wanted %d, got %d', $this->getParameterCount(), $parameterCount));
        }

        return $this;
    }
}
