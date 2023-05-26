<?php

namespace App\Logic\CombatLog\CombatEvents\Traits;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use InvalidArgumentException;

/**
 * @mixin HasParameters
 */
trait ValidatesParameterCount
{
    /**
     * @param array $parameters
     * @return void
     */
    public function validateParameters(array $parameters): void
    {
        if (($parameterCount = count($parameters)) !== $this->getParameterCount()) {
            throw new InvalidArgumentException(sprintf('Invalid parameter count - wanted %d, got %d', $this->getParameterCount(), $parameterCount));
        }
    }
}
