<?php

namespace App\Models\CombatLog;

enum CombatLogAnalyzeStatus
{
    public const Queued     = 0;
    public const Verifying  = 10;
    public const Processing = 20;
    public const Completed  = 30;
    public const Error      = 40;
}


