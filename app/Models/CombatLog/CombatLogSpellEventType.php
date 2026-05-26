<?php

namespace App\Models\CombatLog;

enum CombatLogSpellEventType: string
{
    case SpellCreated    = 'spell_created';
    case PropertyChanged = 'property_changed';
    case PropertyRemoved = 'property_removed';
}
