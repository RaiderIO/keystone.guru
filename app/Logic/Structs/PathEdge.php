<?php

namespace App\Logic\Structs;

readonly class PathEdge
{
    public function __construct(
        public string $fromNodeId,
        public string $toNodeId,
        public float  $weight,
    ) {
    }
}
