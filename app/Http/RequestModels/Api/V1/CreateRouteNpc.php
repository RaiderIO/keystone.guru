<?php

namespace App\Http\RequestModels\Api\V1;

use Carbon\Carbon;

class CreateRouteNpc
{
    public int $npcId;

    public string $spawnUid;

    public Carbon $engagedAt;

    public Carbon $diedAt;

    public CreateRouteNpcCoord $coord;
}
