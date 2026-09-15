<?php

namespace App\Models\Spell;

/**
 * The class (or `general`) a spell belongs to. `spells`.`category` stores it as the `spellcategory.<value>`
 * translation key.
 */
enum SpellCategory: string
{
    case General     = 'general';
    case Warrior     = 'warrior';
    case Hunter      = 'hunter';
    case DeathKnight = 'death_knight';
    case Mage        = 'mage';
    case Priest      = 'priest';
    case Monk        = 'monk';
    case Rogue       = 'rogue';
    case Warlock     = 'warlock';
    case Shaman      = 'shaman';
    case Paladin     = 'paladin';
    case Druid       = 'druid';
    case DemonHunter = 'demon_hunter';
    case Evoker      = 'evoker';
    case Unknown     = 'unknown';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
