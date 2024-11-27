<?php

namespace App\Http\Models\Request\CombatLog\Route;

use Illuminate\Contracts\Support\Arrayable;

class CombatLogRouteCoord implements Arrayable
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

    public static function createFromArray(array $body): CombatLogRouteCoord
    {
        return new CombatLogRouteCoord(
            $body['x'],
            $body['y'],
            $body['uiMapId']
        );
    }
}
