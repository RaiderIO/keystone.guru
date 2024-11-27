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
 */
class CombatLogRouteNpcCorrection extends CombatLogRouteNpc
{
    public function __construct(
        int                        $npcId,
        string                     $spawnUid,
        string                     $engagedAt,
        string                     $diedAt,
        CombatLogRouteCoord        $coord,
        public CombatLogRouteCoord $coordEnemy)
    {
        parent::__construct(
            $npcId,
            $spawnUid,
            $engagedAt,
            $diedAt,
            $coord
        );
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'coordEnemy' => $this->coordEnemy->toArray(),
        ]);
    }

    public static function createFromArray(array $body): CombatLogRouteNpcCorrection
    {
        return new CombatLogRouteNpcCorrection(
            $body['npcId'],
            $body['spawnUid'],
            $body['engagedAt'],
            $body['diedAt'],
            CombatLogRouteCoord::createFromArray($body['coord']),
            CombatLogRouteCoord::createFromArray($body['coordEnemy']),
        );
    }
}
