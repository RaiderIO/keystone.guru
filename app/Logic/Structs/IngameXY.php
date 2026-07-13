<?php

namespace App\Logic\Structs;

use App\Models\Floor\Floor;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class IngameXY implements Arrayable
{
    public function __construct(private float $x = 0, private float $y = 0, private ?Floor $floor = null)
    {
    }

    public function getX(?int $precision = null): float
    {
        // Stabilize the float value by adding a very small number to it
        return $precision === null ? $this->x : round($this->x + 1e-9, $precision, \RoundingMode::HalfAwayFromZero);
    }

    public function setX(float $x): IngameXY
    {
        $this->x = $x;

        return $this;
    }

    public function getY(?int $precision = null): float
    {
        // Stabilize the float value by adding a very small number to it
        return $precision === null ? $this->y : round($this->y + 1e-9, $precision, \RoundingMode::HalfAwayFromZero);
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

    /**
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'x' => $this->getX(),
            'y' => $this->getY(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArrayWithFloor(): array
    {
        return [
            'x'        => $this->x,
            'y'        => $this->y,
            'floor_id' => $this->floor?->id,
        ];
    }

    public function __clone(): void
    {
    }

    /**
     * @param array<string, float> $ingameXY
     */
    public static function fromArray(array $ingameXY, ?Floor $floor): IngameXY
    {
        return new IngameXY($ingameXY['x'], $ingameXY['y'], $floor);
    }
}
