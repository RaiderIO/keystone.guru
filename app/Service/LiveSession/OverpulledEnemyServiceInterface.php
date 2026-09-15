<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;

interface OverpulledEnemyServiceInterface
{
    public function getRouteCorrection(LiveSession $liveSession): DungeonRouteCorrection;
}
