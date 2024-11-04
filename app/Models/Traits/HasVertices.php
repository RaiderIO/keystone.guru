<?php

namespace App\Models\Traits;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

/**
 * @property string $vertices_json
 */
trait HasVertices
{
    /**
     * @return Collection<LatLng>
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

    public function getCoordinatesData(
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        Floor                       $floor
    ): array {
        $latLngs = $this->getDecodedLatLngs();

        $splitFloorsLatLngs = collect();
        $facadeLatLngs      = collect();

        if ($floor->facade) {
            foreach ($latLngs as $latLng) {
                $facadeLatLngs->push($latLng);
                $splitFloorsLatLngs->push(
                    $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng)
                );
            }

        } else {
            foreach ($latLngs as $latLng) {
                $splitFloorsLatLngs->push($latLng);
                $facadeLatLngs->push(
                    $coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $latLng)
                );
            }
        }

        return [
            'coordinates' => [
                User::MAP_FACADE_STYLE_SPLIT_FLOORS => $splitFloorsLatLngs->map(function (LatLng $latLng) {
                    return $latLng->toArrayWithFloor();
                })->toArray(),
                User::MAP_FACADE_STYLE_FACADE       => $facadeLatLngs->map(function (LatLng $latLng) {
                    return $latLng->toArrayWithFloor();
                })->toArray(),
            ],
        ];
    }
}
