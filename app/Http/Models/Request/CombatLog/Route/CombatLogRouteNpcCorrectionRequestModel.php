<?php

namespace App\Http\Models\Request\CombatLog\Route;

/**
 * This class is used when Raider.io sends combat log info to our API, we then push it through ARC and this correction class
 * is used to give info back about its original coordinate AND the corrected coordinate:
 *
 * {
 *     "npcId": 190342,
 *     "spawnUid": "00023C8AAD",
 *     "engagedAt": "2023-07-22T19:04:45.134+00:00",
 *     "diedAt": "2023-07-22T19:05:08.545+00:00",
 *     "coord": {
 *         "x": -184.38,
 *         "y": -17.76,
 *         "uiMapId": 2082
 *     },
 *     "coordEnemy": {
 *         "x": -187.4,
 *         "y": -27.2,
 *         "uiMapId": 2082
 *     }
 * }
 *
 * @OA\Schema(schema="CombatLogRouteNpcCorrection")
 * @OA\Property(property="npcId", type="integer")
 * @OA\Property(property="spawnUid", type="string")
 * @OA\Property(property="engagedAt", type="string", format="date-time")
 * @OA\Property(property="diedAt", type="string", format="date-time")
 * @OA\Property(property="coord", ref="#/components/schemas/CombatLogRouteCoord")
 * @OA\Property(property="coordEnemy", ref="#/components/schemas/CombatLogRouteCoord")
 */
class CombatLogRouteNpcCorrectionRequestModel extends CombatLogRouteNpcRequestModel
{
    public function __construct(
        ?int                                    $npcId = null,
        ?string                                 $spawnUid = null,
        ?string                                 $engagedAt = null,
        ?string                                 $diedAt = null,
        ?CombatLogRouteCoordRequestModel        $coord = null,
        public ?CombatLogRouteCoordRequestModel $coordEnemy = null)
    {
        parent::__construct(
            $npcId,
            $spawnUid,
            $engagedAt,
            $diedAt,
            $coord
        );
    }
}
