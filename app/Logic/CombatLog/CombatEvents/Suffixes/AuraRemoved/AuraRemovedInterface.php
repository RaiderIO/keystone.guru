<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\AuraRemoved;

interface AuraRemovedInterface
{
    public function getAuraType(): string;

    public function getAmount(): ?int;

    public function getUnknown(): ?int;
}
