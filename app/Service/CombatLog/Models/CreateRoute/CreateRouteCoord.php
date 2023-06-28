<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteCoord
{
    public float $x;

    public float $y;

    public int $uiMapId;

    /**
     * @param float $x
     * @param float $y
     * @param int $uiMapId
     */
    public function __construct(float $x, float $y, int $uiMapId)
    {
        $this->x       = $x;
        $this->y       = $y;
        $this->uiMapId = $uiMapId;
    }

    /**
     * @param array $body
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
