<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession;

interface OverpulledEnemyServiceInterface
{
    /**
     * @param LiveSession $liveSession
     * @return DungeonRouteCorrection
     */
    function getRouteCorrection(LiveSession $liveSession): DungeonRouteCorrection;
}