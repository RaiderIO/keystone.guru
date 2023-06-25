<?php

namespace App\Service\CombatLog\Models\CreateRoute;

class CreateRouteNpcCoord
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
     * @return CreateRouteNpcCoord
     */
    public static function createFromArray(array $body): CreateRouteNpcCoord
    {
        return new CreateRouteNpcCoord(
            $body['x'],
            $body['y'],
            $body['uiMapId']
        );
    }
}
