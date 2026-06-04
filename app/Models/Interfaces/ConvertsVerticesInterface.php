<?php

namespace App\Models\Interfaces;

use App\Models\Floor\Floor;
use Illuminate\Support\Collection;

/**
 * @property string $vertices_json
 */
interface ConvertsVerticesInterface
{
    public function getDecodedLatLngs(?Floor $floor = null): Collection;
}
