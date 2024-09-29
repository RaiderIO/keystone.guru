<?php

namespace App\Service\CombatLog\Models\CreateRoute;

use Illuminate\Contracts\Support\Arrayable;

class CreateRouteCoord implements Arrayable
{
    public function __construct(public float $x, public float $y, public int $uiMapId)
    {
    }

    public function toArray(): array
    {
        return [
            'x'       => $this->x,
            'y'       => $this->y,
            'uiMapId' => $this->uiMapId,
        ];
    }

    public static function createFromArray(array $body): CreateRouteCoord
    {
        return new CreateRouteCoord(
            $body['x'],
            $body['y'],
            $body['uiMapId']
        );
    }
}
