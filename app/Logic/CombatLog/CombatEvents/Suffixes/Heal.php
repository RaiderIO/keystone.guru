<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;

class Heal extends Suffix
{
    private int $amount;

    private int $overHealing;

    private int $absorbed;

    private bool $critical;

    /** @var string ex: nil (probably something like isGlancing or isCrushing, but those are not applicable to heals */
    private string $unknown1;

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
    public function getOverHealing(): int
    {
        return $this->overHealing;
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
     * @return string
     */
    public function getUnknown1(): string
    {
        return $this->unknown1;
    }

    /**
     * @param array $parameters
     * @return HasParameters|$this
     */
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->amount      = $parameters[0];
        $this->overHealing = $parameters[1];
        $this->absorbed    = $parameters[2];
        $this->critical    = $parameters[3];
        $this->unknown1    = $parameters[4];

        return $this;
    }


    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 5;
    }
}
