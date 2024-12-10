<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes\Damage;

interface DamageInterface
{
    public function getAmount(): int;

    public function getRawAmount(): int;

    public function getOverKill(): int;

    public function getSchool(): int;

    public function getResisted(): int;

    public function getBlocked(): int;

    public function getAbsorbed(): int;

    public function isCritical(): bool;

    public function isGlancing(): bool;

    public function isCrushing(): bool;
}
