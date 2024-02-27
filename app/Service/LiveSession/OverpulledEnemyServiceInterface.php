<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession;

interface OverpulledEnemyServiceInterface
{
    public function getRouteCorrection(LiveSession $liveSession): DungeonRouteCorrection;
}
