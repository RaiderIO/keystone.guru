<?php

namespace App\Logic\CombatLog\SpecialEvents\Interfaces;

interface HasCombatLogVersionInterface
{
    public function getVersionLong(): int;

    public function isAdvancedLogEnabled(): bool;
}
