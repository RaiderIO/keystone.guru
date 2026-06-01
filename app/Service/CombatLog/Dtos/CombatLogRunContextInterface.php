<?php

namespace App\Service\CombatLog\Dtos;

interface CombatLogRunContextInterface
{
    public function getKeyLevel(): int;

    /**
     * @return int[]
     */
    public function getAffixIds(): array;
}
