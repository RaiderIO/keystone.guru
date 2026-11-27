<?php

namespace App\Logic\Structs;

readonly class PathStep
{
    /**
     * @param bool $arrivedByTransport Whether the step was reached from the previous one by taking a transport, rather
     *                                 than by walking or a floor switch
     */
    public function __construct(
        public LatLng $latLng,
        public bool   $arrivedByTransport = false,
    ) {
    }
}
