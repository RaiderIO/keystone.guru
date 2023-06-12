<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Damage extends Suffix
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

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getRawAmount(): int
    {
        return $this->rawAmount;
    }

    /**
     * @return int
     */
    public function getOverKill(): int
    {
        return $this->overKill;
    }

    /**
     * @return int
     */
    public function getSchool(): int
    {
        return $this->school;
    }

    /**
     * @return int
     */
    public function getResisted(): int
    {
        return $this->resisted;
    }

    /**
     * @return int
     */
    public function getBlocked(): int
    {
        return $this->blocked;
    }

    /**
     * @return int
     */
    public function getAbsorbed(): int
    {
        return $this->absorbed;
    }

    /**
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->critical;
    }

    /**
     * @return bool
     */
    public function isGlancing(): bool
    {
        return $this->glancing;
    }

    /**
     * @return bool
     */
    public function isCrushing(): bool
    {
        return $this->crushing;
    }


    /**
     * @param array $parameters
     * @return HasParameters
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->amount    = $parameters[0];
        $this->rawAmount = $parameters[1];
        $this->overKill  = $parameters[2];
        $this->school    = $parameters[3];
        $this->resisted  = $parameters[4];
        $this->blocked   = $parameters[5];
        $this->absorbed  = $parameters[6];
        $this->critical  = $parameters[7] !== 'nil';
        $this->glancing  = $parameters[8] !== 'nil';
        $this->crushing  = $parameters[9] !== 'nil';


        return $this;
    }


    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 10;
    }
}
