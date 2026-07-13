<?php

namespace App\Logic\CombatLog\CombatEvents\Advanced;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

interface AdvancedDataInterface extends HasParameters
{
    public function getInfoGuid(): ?Guid;

    public function getOwnerGuid(): ?Guid;

    public function getCurrentHP(): int;

    public function getMaxHP(): int;

    public function getAttackPower(): int;

    public function getSpellPower(): int;

    public function getArmor(): int;

    public function getAbsorb(): int;

    /**
     * @return array<int, mixed>
     */
    public function getPowerType(): array;

    /**
     * @return array<int, mixed>
     */
    public function getCurrentPower(): array;

    /**
     * @return array<int, mixed>
     */
    public function getMaxPower(): array;

    /**
     * @return array<int, mixed>
     */
    public function getPowerCost(): array;

    public function getPositionX(): float;

    public function getPositionY(): float;

    public function getUiMapId(): int;

    public function getFacing(): float;

    public function getLevel(): int;
}
