<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;

interface AuraAppliedInterface
{
    public function getAuraType(): string;

    public function getAmount(): ?int;

    public function getUnknown(): ?int;
}
