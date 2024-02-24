<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Missed extends Suffix
{
    private string $missType;

    private bool $isOffHand;

    private int $amountMissed;

    private int $unknown1;

    private bool $critical;

    /**
     * @return string
     */
    public function getMissType(): string
    {
        return $this->missType;
    }

    /**
     * @return bool
     */
    public function isOffHand(): bool
    {
        return $this->isOffHand;
    }

    /**
     * @return int
     */
    public function getAmountMissed(): int
    {
        return $this->amountMissed;
    }

    /**
     * @return int
     */
    public function getUnknown1(): int
    {
        return $this->unknown1;
    }

    /**
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->critical;
    }


    /**
     * @param array $parameters
     * @return HasParameters
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->missType  = $parameters[0];
        $this->isOffHand = $parameters[1] !== 'nil';
        // May be set for absorbs, blocks
        if (isset($parameters[2])) {
            $this->amountMissed = $parameters[2];
        }
        // May be set for absorbs
        if (isset($parameters[3])) {
            $this->unknown1 = $parameters[3];
        }
        // May be set for absorbs
        if (isset($parameters[4])) {
            $this->critical = $parameters[4] !== 'nil';
        }

        return $this;
    }


    /**
     * @return int
     */
    public function getOptionalParameterCount(): int
    {
        return 3;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 5;
    }
}
