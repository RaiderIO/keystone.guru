<?php

namespace App\Logic\Structs;

readonly class PathNode
{
    public function __construct(
        public string   $id,
        public LatLng   $latLng,
        public IngameXY $ingameXY,
    ) {
    }
}
