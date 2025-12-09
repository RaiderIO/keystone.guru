<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

class HealAbsorbed extends Suffix
{
    private ?Guid $extraGUID = null;

    private string $extraName;

    private string $extraFlags;

    private string $extraRaidFlags;

    private int $extraSpellId;

    private string $extraSpellName;

    private string $extraSchool;

    private int $absorbedAmount;

    private int $totalAmount;

    public function getExtraGUID(): ?Guid
    {
        return $this->extraGUID;
    }

    public function getExtraName(): string
    {
        return $this->extraName;
    }

    public function getExtraFlags(): string
    {
        return $this->extraFlags;
    }

    public function getExtraRaidFlags(): string
    {
        return $this->extraRaidFlags;
    }

    public function getExtraSpellId(): int
    {
        return $this->extraSpellId;
    }

    public function getExtraSpellName(): string
    {
        return $this->extraSpellName;
    }

    public function getExtraSchool(): string
    {
        return $this->extraSchool;
    }

    public function getAbsorbedAmount(): int
    {
        return $this->absorbedAmount;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    /**
     * @return HasParameters|$this
     */
    #[\Override]
    public function setParameters(array $parameters): HasParameters
    {
        parent::setParameters($parameters);

        $this->extraGUID      = Guid::createFromGuidString($parameters[0]);
        $this->extraName      = $parameters[1];
        $this->extraFlags     = $parameters[2];
        $this->extraRaidFlags = $parameters[3];
        $this->extraSpellId   = $parameters[4];
        $this->extraSpellName = $parameters[5];
        $this->extraSchool    = $parameters[6];
        $this->absorbedAmount = $parameters[7];

        if (isset($parameters[8])) {
            $this->totalAmount = $parameters[8];
        }

        return $this;
    }

    public function getOptionalParameterCount(): int
    {
        return 1;
    }

    public function getParameterCount(): int
    {
        return 9;
    }
}
