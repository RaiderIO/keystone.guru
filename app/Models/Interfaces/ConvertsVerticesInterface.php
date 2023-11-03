<?php

namespace App\Models\Interfaces;

use App\Models\Floor\Floor;
use App\Models\Traits\HasVertices;
use Illuminate\Support\Collection;

/**
 * @mixin HasVertices
 */
interface ConvertsVerticesInterface
{
    public function getDecodedLatLngs(?Floor $floor = null): Collection;
}
