<?php

namespace App\Logic\CombatLog\CombatEvents\Traits;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use InvalidArgumentException;

/**
 * @mixin HasParameters
 */
trait ValidatesParameterCount
{
    public function getOptionalParameterCount(): int
    {
        return 0;
    }

    public function validateParameters(array $parameters): void
    {
        $parameterCount = count($parameters);

        if ($parameterCount < $this->getParameterCount() - $this->getOptionalParameterCount()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid parameter count for %s - wanted %d-%d, got %d',
                    $this::class,
                    $this->getParameterCount() - $this->getOptionalParameterCount(),
                    $this->getParameterCount(),
                    $parameterCount,
                ),
            );
        } elseif ($parameterCount > $this->getParameterCount()) {
            throw new InvalidArgumentException(
                sprintf('Invalid parameter count for %s - wanted %d, got %d', $this::class, $this->getParameterCount(), $parameterCount),
            );
        }
    }
}
