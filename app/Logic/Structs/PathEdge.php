<?php

namespace App\Logic\Structs;

readonly class PathEdge
{
    /**
     * @param list<LatLng> $waypoints   the points the edge passes through between its two nodes, in travel order
     * @param bool         $isTransport whether the edge is taken by a transport instead of walked
     */
    public function __construct(
        public string $fromNodeId,
        public string $toNodeId,
        public float  $weight,
        public array  $waypoints = [],
        public bool   $isTransport = false,
    ) {
    }
}
