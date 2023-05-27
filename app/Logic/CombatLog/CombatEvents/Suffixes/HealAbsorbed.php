<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

class HealAbsorbed extends Suffix
{
    private ?Guid $extraGUID;

    private string $extraName;

    private string $extraFlags;

    private string $extraRaidFlags;

    private int $extraSpellId;

    private string $extraSpellName;

    private string $extraSchool;

    private int $absorbedAmount;

    private int $totalAmount;

    /**
     * @return Guid|null
     */
    public function getExtraGUID(): ?Guid
    {
        return $this->extraGUID;
    }

    /**
     * @return string
     */
    public function getExtraName(): string
    {
        return $this->extraName;
    }

    /**
     * @return string
     */
    public function getExtraFlags(): string
    {
        return $this->extraFlags;
    }

    /**
     * @return string
     */
    public function getExtraRaidFlags(): string
    {
        return $this->extraRaidFlags;
    }

    /**
     * @return int
     */
    public function getExtraSpellId(): int
    {
        return $this->extraSpellId;
    }

    /**
     * @return string
     */
    public function getExtraSpellName(): string
    {
        return $this->extraSpellName;
    }

    /**
     * @return string
     */
    public function getExtraSchool(): string
    {
        return $this->extraSchool;
    }

    /**
     * @return int
     */
    public function getAbsorbedAmount(): int
    {
        return $this->absorbedAmount;
    }

    /**
     * @return int
     */
    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    /**
     * @param array $parameters
     * @return HasParameters|$this
     */
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

    /**
     * @return int
     */
    public function getOptionalParameterCount(): int
    {
        return 1;
    }

    /**
     * @return int
     */
    public function getParameterCount(): int
    {
        return 9;
    }
}
