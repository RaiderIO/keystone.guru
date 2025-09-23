<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V20;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\MissedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\Guid\Guid;

class MissedV20 extends Suffix implements MissedInterface
{
    private Guid $missType;

    private bool $offhand;

    private int $amountMissed;

    private int $amountTotal;

    private bool $critical;

    public function getMissType(): Guid
    {
        return $this->missType;
    }

    public function isOffHand(): bool
    {
        return $this->offhand;
    }

    public function getAmountMissed(): int
    {
        return $this->amountMissed;
    }

    public function getAmountTotal(): int
    {
        return $this->amountTotal;
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function getDamageType(): ?string
    {
        return null;
    }

    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->missType = Guid::createFromGuidString($parameters[0]);
        $this->offhand  = $parameters[1] !== 'nil';
        // May be set for absorbs, blocks
        $this->amountMissed = $parameters[2] ?? 0;
        // May be set for absorbs
        $this->amountTotal = $parameters[3] ?? 0;
        // May be set for absorbs
        $this->critical = ($parameters[4] ?? 'nil') !== 'nil';

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
