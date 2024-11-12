<?php

namespace App\Models\SimulationCraft;

enum SimulationCraftRaidBuffs: int
{
    case Bloodlust          = 1;
    case ArcaneIntellect    = 2;
    case PowerWordFortitude = 4;
    case MarkOfTheWild      = 8;
    case BattleShout        = 16;
    case MysticTouch        = 32;
    case ChaosBrand         = 64;
    case Skyfury            = 128;
    case HuntersMark        = 256;
    case PowerInfusion      = 512;
    case Bleeding           = 1024;
}
