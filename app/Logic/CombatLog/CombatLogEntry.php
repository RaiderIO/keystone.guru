<?php

namespace App\Logic\CombatLog;

use Illuminate\Support\Carbon;

class CombatLogEntry
{
    private Carbon $timestamp;

    private string $event;
}
