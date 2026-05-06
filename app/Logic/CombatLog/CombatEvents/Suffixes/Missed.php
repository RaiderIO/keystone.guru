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

    public function getMissType(): string
    {
        return $this->missType;
    }

    public function isOffHand(): bool
    {
        return $this->isOffHand;
    }

    public function getAmountMissed(): int
    {
        return $this->amountMissed;
    }

    public function getUnknown1(): int
    {
        return $this->unknown1;
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    #[\Override]
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

    public function getOptionalParameterCount(): int
    {
        return 3;
    }

    public function getParameterCount(): int
    {
        return 5;
    }
}
