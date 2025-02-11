<?php

namespace App\Logic\Structs;

use App\Models\Floor\Floor;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Contracts\Support\Arrayable;

readonly class MapBounds implements Arrayable
{
    public function __construct(
        private float $minIngameX = 0,
        private float $minIngameY = 0,
        private float $maxIngameX = 0,
        private float $maxIngameY = 0)
    {
    }

    public function getMinIngameX(): float
    {
        return $this->minIngameX;
    }

    public function getMinIngameY(): float
    {
        return $this->minIngameY;
    }

    public function getMaxIngameX(): float
    {
        return $this->maxIngameX;
    }

    public function getMaxIngameY(): float
    {
        return $this->maxIngameY;
    }

    public function toArray(): array
    {
        return [
            'minIngameX' => $this->minIngameX,
            'minIngameY' => $this->minIngameY,
            'maxIngameX' => $this->maxIngameX,
            'maxIngameY' => $this->maxIngameY,
        ];
    }
}
