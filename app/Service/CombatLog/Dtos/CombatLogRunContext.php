<?php

namespace App\Service\CombatLog\Dtos;

readonly class CombatLogRunContext implements CombatLogRunContextInterface
{
    /**
     * @param int[] $affixIds
     */
    public function __construct(
        public int   $keyLevel,
        public array $affixIds,
    ) {
    }

    public function getKeyLevel(): int
    {
        return $this->keyLevel;
    }

    /**
     * @return int[]
     */
    public function getAffixIds(): array
    {
        return $this->affixIds;
    }
}
