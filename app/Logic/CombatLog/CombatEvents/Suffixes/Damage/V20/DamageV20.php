<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\DamageInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;

class DamageV20 extends Suffix implements DamageInterface
{
    private int $amount;

    private int $rawAmount;

    private int $overKill;

    private int $school;

    private int $resisted;

    private int $blocked;

    private int $absorbed;

    private bool $critical;

    private bool $glancing;

    private bool $crushing;

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getRawAmount(): int
    {
        return $this->rawAmount;
    }

    public function getOverKill(): int
    {
        return $this->overKill;
    }

    public function getSchool(): int
    {
        return $this->school;
    }

    public function getResisted(): int
    {
        return $this->resisted;
    }

    public function getBlocked(): int
    {
        return $this->blocked;
    }

    public function getAbsorbed(): int
    {
        return $this->absorbed;
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function isGlancing(): bool
    {
        return $this->glancing;
    }

    public function isCrushing(): bool
    {
        return $this->crushing;
    }

    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->amount    = $parameters[0];
        $this->rawAmount = $parameters[1];
        $this->overKill  = $parameters[2];
        $this->school    = $parameters[3];
        $this->resisted  = $parameters[4] === 'nil' ? 0 : $parameters[4];
        $this->blocked   = $parameters[5] === 'nil' ? 0 : $parameters[5];
        $this->absorbed  = $parameters[6] === 'nil' ? 0 : $parameters[6];
        $this->critical  = $parameters[7] !== 'nil';
        $this->glancing  = $parameters[8] !== 'nil';
        $this->crushing  = $parameters[9] !== 'nil';

        return $this;
    }

    public function getParameterCount(): int
    {
        return 10;
    }
}
