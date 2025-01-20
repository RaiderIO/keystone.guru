<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Missed\V9SoD;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\MissedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\Guid\Guid;
use App\Logic\CombatLog\Guid\MissType\Block;
use App\Logic\CombatLog\Guid\MissType\Resist;

class MissedV9SoD extends Suffix implements MissedInterface
{
    private Guid $missType;

    private bool $offhand;

    private int $amountMissed;

    private int $amountTotal;

    private ?string $damageType;

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

    public function getDamageType(): ?string
    {
        return $this->damageType;
    }

    public function isCritical(): bool
    {
        return false;
    }


    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->missType = Guid::createFromGuidString($parameters[0]);
        $this->offhand  = $parameters[1] !== 'nil';
        if (!isset($parameters[2]) || in_array($parameters[2], ['ST', 'AOE'])) {
            $this->amountMissed = 0;
            $this->amountTotal  = 0;
            $this->damageType   = $parameters[2] ?? null;
        } else if ($this->missType instanceof Block || $this->missType instanceof Resist) {
            $this->amountMissed = 0;
            $this->amountTotal  = 0;
            $this->damageType   = $parameters[2];
        } else {
            $this->amountMissed = $parameters[2];
            $this->amountTotal  = $parameters[3] ?? 0;
            $this->damageType   = $parameters[4] ?? null;
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
