<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession;

interface OverpulledEnemyServiceInterface
{
    /**
     * @return DungeonRouteCorrection
     */
    function getRouteCorrection(LiveSession $liveSession): DungeonRouteCorrection;
}