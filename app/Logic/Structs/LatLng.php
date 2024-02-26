<?php

namespace App\Logic\Structs;

use App\Models\Floor\Floor;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Contracts\Support\Arrayable;

class LatLng implements Arrayable
{
    public function __construct(private float $lat = 0, private float $lng = 0, private ?Floor $floor = null)
    {
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function setLat(float $lat): LatLng
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    public function setLng(float $lng): LatLng
    {
        $this->lng = $lng;

        return $this;
    }

    public function getFloor(): ?Floor
    {
        return $this->floor;
    }

    public function setFloor(?Floor $floor): LatLng
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * @return $this
     */
    public function scale(LatLng $currentMapCenter, int $currentMapSize, LatLng $targetMapCenter, int $targetMapSize): self
    {
        $currentMapSizeLat = $currentMapSize;
        $currentMapSizeLng = $currentMapSize * CoordinatesService::MAP_ASPECT_RATIO;
        // Lat is inverted. The dead center is top left, not bottom left
        $currentMapOffsetLat = $currentMapCenter->getLat() + ($currentMapSizeLat / 2);
        $currentMapOffsetLng = $currentMapCenter->getLng() - ($currentMapSizeLng / 2);

        $targetMapSizeLat = $targetMapSize;
        $targetMapSizeLng = $targetMapSize * CoordinatesService::MAP_ASPECT_RATIO;
        // Lat is inverted. The dead center is top left, not bottom left
        $targetMapOffsetLat = $targetMapCenter->getLat() + ($targetMapSizeLat / 2);
        $targetMapOffsetLng = $targetMapCenter->getLng() - ($targetMapSizeLng / 2);

        // Undo the offset. Then scale by the correct factor, and apply the new offset
        $this->lat = (($this->lat - $currentMapOffsetLat) * ($targetMapSizeLat / $currentMapSizeLat)) + $targetMapOffsetLat;
        $this->lng = (($this->lng - $currentMapOffsetLng) * ($targetMapSizeLng / $currentMapSizeLng)) + $targetMapOffsetLng;

        return $this;
    }

    public function rotate(LatLng $centerLatLng, float $degrees): self
    {
        $lng1 = $this->lng - $centerLatLng->lng;
        $lat1 = $this->lat - $centerLatLng->lat;

        $angle = $degrees * (pi() / 180);

        $lng2 = $lng1 * cos($angle) - $lat1 * sin($angle);
        $lat2 = $lng1 * sin($angle) + $lat1 * cos($angle);

        $this->lng = $lng2 + $centerLatLng->lng;
        $this->lat = $lat2 + $centerLatLng->lat;

        return $this;
    }

    /**
     * Only use this when saving the end result to models, please!
     * Trying to get rid of this structure as much as possible by using this class in the first place.
     */
    public function toArray(): array
    {
        return ['lat' => $this->lat, 'lng' => $this->lng];
    }

    public function __clone()
    {
        return new LatLng(
            $this->lat,
            $this->lng,
            $this->floor
        );
    }

    /**
     * @param array{lat: float, lng: float} $latLng
     */
    public static function fromArray(array $latLng, ?Floor $floor = null): LatLng
    {
        return new LatLng($latLng['lat'], $latLng['lng'], $floor);
    }
}
