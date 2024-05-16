<?php

namespace App\Logic\Structs;

use App\Models\Floor\Floor;
use Illuminate\Contracts\Support\Arrayable;

class IngameXY implements Arrayable
{
    private ?LatLng $latLng = null;

    public function __construct(private float $x = 0, private float $y = 0, private ?Floor $floor = null)
    {
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function setX(float $x): IngameXY
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function setY(float $y): IngameXY
    {
        $this->y = $y;

        return $this;
    }

    public function getFloor(): ?Floor
    {
        return $this->floor;
    }

    public function setFloor(?Floor $floor): IngameXY
    {
        $this->floor = $floor;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'x' => $this->getX(),
            'y' => $this->getY(),
        ];
    }

    public function toArrayWithFloor(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'floor_id' => optional($this->floor)->id];
    }

    public function __clone()
    {
        return new IngameXY(
            $this->x,
            $this->y,
            $this->floor
        );
    }

    public static function fromArray(array $ingameXY, ?Floor $floor): IngameXY
    {
        return new IngameXY($ingameXY['x'], $ingameXY['y'], $floor);
    }
}
