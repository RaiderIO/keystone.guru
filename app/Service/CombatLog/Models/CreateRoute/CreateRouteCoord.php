<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteCoord
{
    public function __construct(public float $x, public float $y, public int $uiMapId)
    {
    }

    /**
     * @return CreateRouteCoord
     */
    public static function createFromArray(array $body): CreateRouteCoord
    {
        return new CreateRouteCoord(
            $body['x'],
            $body['y'],
            $body['uiMapId']
        );
    }
}
