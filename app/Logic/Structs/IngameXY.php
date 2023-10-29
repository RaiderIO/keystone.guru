<?php

namespace App\Logic\Structs;

use App\Models\Floor\Floor;
use Illuminate\Contracts\Support\Arrayable;

class IngameXY implements Arrayable
{
    private float $x;

    private float $y;

    private ?Floor $floor;

    private ?LatLng $latLng = null;

    /**
     * @param float      $x
     * @param float      $y
     * @param Floor|null $floor
     */
    public function __construct(float $x = 0, float $y = 0, ?Floor $floor = null)
    {
        $this->x     = $x;
        $this->y     = $y;
        $this->floor = $floor;
    }

    /**
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * @param float $x
     *
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
     * @param float $y
     *
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
     * @param array      $ingameXY
     * @param Floor|null $floor
     *
     * @return IngameXY
     */
    public static function fromArray(array $ingameXY, ?Floor $floor): IngameXY
    {
        return new IngameXY($ingameXY['x'], $ingameXY['y'], $floor);
    }
}
