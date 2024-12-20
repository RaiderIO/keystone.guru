<?php

namespace App\Logic\CombatLog\SpecialEvents\CombatantInfo;

use App\Logic\CombatLog\Guid\Player;

interface CombatantInfoInterface
{
    public function getPlayerGuid(): ?Player;

    public function getFaction(): int;

    public function getStrength(): int;

    public function getAgility(): int;

    public function getStamina(): int;

    public function getIntellect(): int;

    public function getDodge(): int;

    public function getParry(): int;

    public function getBlock(): int;

    public function getCritMelee(): int;

    public function getCritRanged(): int;

    public function getCritSpell(): int;

    public function getSpeed(): int;

    public function getLifesteal(): int;

    public function getHasteMelee(): int;

    public function getHasteRanged(): int;

    public function getHasteSpell(): int;

    public function getAvoidance(): int;

    public function getMastery(): int;

    public function getVersatilityDamageDone(): int;

    public function getVersatilityHealingDone(): int;

    public function getVersatilityDamageTaken(): int;

    public function getArmor(): int;

    public function getCurrentSpecId(): int;

    /**
     * 0 = item ID
     * 1 = item level
     * @return array{0: int, 1: int}
     */
    public function getTalents(): array;

    public function getPvpTalents(): array;

    public function getAverageItemLevel(): float;

    public function getInterestingAuras(): array;

    public function getHonorLevel(): int;

    public function getSeason(): int;

    public function getRating(): int;

    public function getTier(): int;

    public function getEquippedItems(): array;
}
