<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Missed;

use App\Logic\CombatLog\Guid\Guid;

interface MissedInterface
{
    public function getMissType(): Guid;

    public function isOffHand(): bool;

    public function getAmountMissed(): int;

    public function getAmountTotal(): int;

    public function isCritical(): bool;

    public function getDamageType(): ?string;
}
