<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use Override;

/**
 * The combat log file writes `amount, baseAmount, overhealing, absorbed, critical` for every supported
 * combat log version. `baseAmount` is file-only (advanced combat logging) and absent from the
 * COMBAT_LOG_EVENT API, which is why the API documentation lists one field fewer.
 */
class Heal extends Suffix
{
    private int $amount;

    /** @var int The amount before critical strike bonus and before percent modifiers on the target */
    private int $baseAmount;

    private int $overHealing;

    private int $absorbed;

    private bool $critical;

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getBaseAmount(): int
    {
        return $this->baseAmount;
    }

    public function getOverHealing(): int
    {
        return $this->overHealing;
    }

    public function getAbsorbed(): int
    {
        return $this->absorbed;
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    /**
     * @return HasParameters|$this
     */
    #[Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->amount      = $parameters[0];
        $this->baseAmount  = $parameters[1];
        $this->overHealing = $parameters[2];
        $this->absorbed    = $parameters[3];
        $this->critical    = $parameters[4] !== 'nil';

        return $this;
    }

    public function getParameterCount(): int
    {
        return 5;
    }
}
