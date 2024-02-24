<?php

namespace App\Logic\Structs;

use App\Models\Floor\Floor;
use Illuminate\Contracts\Support\Arrayable;

class IngameXY implements Arrayable
{
    private ?LatLng $latLng = null;

    /**
     * @param Floor|null $floor
     */
    public function __construct(private float $x = 0, private float $y = 0, private ?Floor $floor = null)
    {
    }

    /**
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * @return IngameXY
     */
    public function setX(float $x): IngameXY
    {
        $this->x = $x;

        return $this;
    }

    /**
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @return IngameXY
     */
    public function setY(float $y): IngameXY
    {
        $this->y = $y;

        return $this;
    }

    /**
     * @return Floor|null
     */
    public function getFloor(): ?Floor
    {
        return $this->floor;
    }

    /**
     * @param Floor|null $floor
     *
     * @return IngameXY
     */
    public function setFloor(?Floor $floor): IngameXY
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'x' => $this->getX(),
            'y' => $this->getY(),
        ];
    }

    public function __clone()
    {
        return new IngameXY(
            $this->x,
            $this->y,
            $this->floor
        );
    }

    /**
     * @param Floor|null $floor
     * @return IngameXY
     */
    public static function fromArray(array $ingameXY, ?Floor $floor): IngameXY
    {
        return new IngameXY($ingameXY['x'], $ingameXY['y'], $floor);
    }
}
