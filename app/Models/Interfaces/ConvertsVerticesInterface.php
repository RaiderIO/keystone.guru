<?php

namespace App\Models\Interfaces;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use Illuminate\Support\Collection;

/**
 * @property string $vertices_json
 */
interface ConvertsVerticesInterface
{
    /** @return Collection<int, LatLng> */
    public function getDecodedLatLngs(?Floor $floor = null): Collection;
}
