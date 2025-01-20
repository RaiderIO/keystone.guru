<?php

namespace App\Logic\CombatLog\SpecialEvents\CombatantInfo\Versions\V21;

use App\Logic\CombatLog\CombatLogStringParser;
use App\Logic\CombatLog\Guid\Guid;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\CombatantInfoInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;

class CombatantInfoV21 extends SpecialEvent implements CombatantInfoInterface
{
    private Player $playerGuid;
    private int   $faction;
    private int   $strength;
    private int   $agility;
    private int   $stamina;
    private int   $intellect;
    private int   $dodge;
    private int   $parry;
    private int   $block;
    private int   $critMelee;
    private int   $critRanged;
    private int   $critSpell;
    private int   $speed;
    private int   $lifesteal;
    private int   $hasteMelee;
    private int   $hasteRanged;
    private int   $hasteSpell;
    private int   $avoidance;
    private int   $mastery;
    private int   $versatilityDamageDone;
    private int   $versatilityHealingDone;
    private int   $versatilityDamageTaken;
    private int   $armor;
    private int   $currentSpecId;
    private array $talents;
    private array $pvpTalents;
    private array $equippedItems;
    private array $interestingAuras;
    private int   $honorLevel;
    private int   $season;
    private int   $rating;
    private int   $tier;


    public function getPlayerGuid(): ?Player
    {
        return $this->playerGuid;
    }

    public function getFaction(): int
    {
        return $this->faction;
    }

    public function getStrength(): int
    {
        return $this->strength;
    }

    public function getAgility(): int
    {
        return $this->agility;
    }

    public function getStamina(): int
    {
        return $this->stamina;
    }

    public function getIntellect(): int
    {
        return $this->intellect;
    }

    public function getSpirit(): int
    {
        return 0;
    }

    public function getDodge(): int
    {
        return $this->dodge;
    }

    public function getParry(): int
    {
        return $this->parry;
    }

    public function getBlock(): int
    {
        return $this->block;
    }

    public function getCritMelee(): int
    {
        return $this->critMelee;
    }

    public function getCritRanged(): int
    {
        return $this->critRanged;
    }

    public function getCritSpell(): int
    {
        return $this->critSpell;
    }

    public function getSpeed(): int
    {
        return $this->speed;
    }

    public function getLifesteal(): int
    {
        return $this->lifesteal;
    }

    public function getHasteMelee(): int
    {
        return $this->hasteMelee;
    }

    public function getHasteRanged(): int
    {
        return $this->hasteRanged;
    }

    public function getHasteSpell(): int
    {
        return $this->hasteSpell;
    }

    public function getAvoidance(): int
    {
        return $this->avoidance;
    }

    public function getMastery(): int
    {
        return $this->mastery;
    }

    public function getVersatilityDamageDone(): int
    {
        return $this->versatilityDamageDone;
    }

    public function getVersatilityHealingDone(): int
    {
        return $this->versatilityHealingDone;
    }

    public function getVersatilityDamageTaken(): int
    {
        return $this->versatilityDamageTaken;
    }

    public function getArmor(): int
    {
        return $this->armor;
    }

    public function getCurrentSpecId(): int
    {
        return $this->currentSpecId;
    }

    public function getTalents(): array
    {
        return $this->talents;
    }

    public function getPvpTalents(): array
    {
        return $this->pvpTalents;
    }

    public function getEquippedItems(): array
    {
        return $this->equippedItems;
    }

    public function getInterestingAuras(): array
    {
        return $this->interestingAuras;
    }

    public function getHonorLevel(): int
    {
        return $this->honorLevel;
    }

    public function getSeason(): int
    {
        return $this->season;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getTier(): int
    {
        return $this->tier;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        // If GUID is null at this point this will crash - but that's okay, we NEED this to be set
        $playerGuid = Guid::createFromGuidString($parameters[0]);
        if (!($playerGuid instanceof Player)) {
            throw new \Exception('PlayerGuid is not a Player');
        }
        $this->playerGuid = $playerGuid;
        $this->faction                = (int)$parameters[1];
        $this->strength               = (int)$parameters[2];
        $this->agility                = (int)$parameters[3];
        $this->stamina                = (int)$parameters[4];
        $this->intellect              = (int)$parameters[5];
        $this->dodge                  = (int)$parameters[6];
        $this->parry                  = (int)$parameters[7];
        $this->block                  = (int)$parameters[8];
        $this->critMelee              = (int)$parameters[9];
        $this->critRanged             = (int)$parameters[10];
        $this->critSpell              = (int)$parameters[11];
        $this->speed                  = (int)$parameters[12];
        $this->lifesteal              = (int)$parameters[13];
        $this->hasteMelee             = (int)$parameters[14];
        $this->hasteRanged            = (int)$parameters[15];
        $this->hasteSpell             = (int)$parameters[16];
        $this->avoidance              = (int)$parameters[17];
        $this->mastery                = (int)$parameters[18];
        $this->versatilityDamageDone  = (int)$parameters[19];
        $this->versatilityHealingDone = (int)$parameters[20];
        $this->versatilityDamageTaken = (int)$parameters[21];
        $this->armor                  = (int)$parameters[22];
        $this->currentSpecId          = (int)$parameters[23];
        $this->talents                = CombatLogStringParser::parseBracketedString($parameters[24]);
        $this->pvpTalents             = CombatLogStringParser::parseBracketedString($parameters[25]);
        $this->equippedItems          = CombatLogStringParser::parseBracketedString($parameters[26]);
        $this->interestingAuras       = CombatLogStringParser::parseBracketedString($parameters[27]);
        $this->honorLevel             = (int)$parameters[28];
        $this->season                 = (int)$parameters[29];
        $this->rating                 = (int)$parameters[30];
        $this->tier                   = (int)$parameters[31];

        return $this;
    }

    public function getAverageItemLevel(): float
    {
        // 3 = shirt, don't count it
        // 15 = main hand, x2 if 16 has ilvl 0 (= not equipped)
        // 16 = off hand
        // 17 = tabard, don't count it

        // Column 0 is the item id, column 1 is the item level
        return array_sum(array_column($this->equippedItems, 1)) / (count($this->equippedItems) - 3 + ($this->equippedItems[15][1] === 0 ? 2 : 1));
    }

    public function getOptionalParameterCount(): int
    {
        return 0;
    }

    public function getParameterCount(): int
    {
        return 32;
    }
}
