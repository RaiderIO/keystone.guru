<?php

namespace App\Models\CombatLog;

enum CombatLogNpcEventType: string
{
    case CharacteristicAdded   = 'characteristic_added';
    case CharacteristicRemoved = 'characteristic_removed';
    case SpellAssigned         = 'spell_assigned';
}
