<?php

namespace App\Logic\CombatLog\CombatEvents\Suffixes;

interface SuffixBuilderInterface
{
    public static function create(int $combatLogVersion): Suffix;
}
