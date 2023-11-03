<?php

namespace App\Models\Traits;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use Illuminate\Support\Collection;

/**
 * @property string $vertices_json
 */
trait HasVertices
{
    /**
     * @return Collection|LatLng[]
     */
    public function getDecodedLatLngs(?Floor $floor = null): Collection
    {
        $result = collect();

        $decoded = json_decode($this->vertices_json, true);

        if (is_array($decoded)) {
            foreach ($decoded as $latLng) {
                $result->push(new LatLng($latLng['lat'], $latLng['lng'], $floor));
            }
        }

        return $result;
    }
}
