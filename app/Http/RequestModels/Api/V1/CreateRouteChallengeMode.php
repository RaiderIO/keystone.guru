<?php

namespace App\Http\RequestModels\Api\V1;

use Carbon\Carbon;

class CreateRouteChallengeMode
{
    public Carbon $start;

    public Carbon $end;

    public int $durationMs;

    public int $zoneId;

    public int $level;

    public array $affixes;
}
