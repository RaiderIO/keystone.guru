<?php

namespace App\Models\Spell;

/**
 * The cooldown group a spell belongs to. `spells`.`cooldown_group` stores it as the `spellcooldowngroup.<value>`
 * translation key.
 */
enum SpellCooldownGroup: string
{
    case All          = 'all';
    case CdExternal   = 'cd_external';
    case CdGroup      = 'cd_group';
    case CdPersonal   = 'cd_personal';
    case DrExternal   = 'dr_external';
    case DrGroup      = 'dr_group';
    case DrPersonal   = 'dr_personal';
    case GroupDr      = 'group_dr';
    case GroupHealDps = 'group_heal_dps';
    case Immunity     = 'immunity';
    case Movement     = 'movement';
    case Personal     = 'personal';
    case PersonalCd   = 'personal_cd';
    case Utility      = 'utility';
    case Unknown      = 'unknown';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
