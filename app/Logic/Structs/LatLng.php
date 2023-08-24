<?php

namespace App\Logic\Structs;

use App\Models\Floor;

class LatLng
{
    private float $lat;

    private float $lng;

    private ?Floor $floor;

    private ?IngameXY $ingameXY = null;

    /**
     * @param float      $lat
     * @param float      $lng
     * @param Floor|null $floor
     */
    public function __construct(float $lat = 0, float $lng = 0, ?Floor $floor = null)
    {
        $this->lat   = $lat;
        $this->lng   = $lng;
        $this->floor = $floor;
    }

    /**
     * @return float
     */
    public function getLat(): float
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     *
     * @return LatLng
     */
    public function setLat(float $lat): LatLng
    {
        $this->lat = $lat;
        return $this;
    }

    /**
     * @return float
     */
    public function getLng(): float
    {
        return $this->lng;
    }

    /**
     * @param float $lng
     *
     * @return LatLng
     */
    public function setLng(float $lng): LatLng
    {
        $this->lng = $lng;
        return $this;
    }

    /**
     * @return Floor|null
     */
    public function getFloor(): ?Floor
    {
        return $this->floor;
    }

    /**
     * @param Floor|null $floor
     *
     * @return LatLng
     */
    public function setFloor(?Floor $floor): LatLng
    {
        $this->floor = $floor;
        return $this;
    }

    /**
     * @return IngameXY|null
     */
    public function getIngameXY(): ?IngameXY
    {
        return $this->ingameXY ?? ($this->ingameXY = $this->calculateIngameCoordinates());
    }

    /**
     * @return void
     */
    private function calculateIngameCoordinates(): ?IngameXY
    {
        $result = null;

        if ($this->floor !== null) {
            $ingameXY = $this->floor->calculateIngameLocationForMapLocation(
                $this->lat,
                $this->lng
            );

            $result = IngameXY::fromArray($ingameXY, $this->floor);
        }

        return $result;
    }

    /**
     * @param array      $latLng
     * @param Floor|null $floor
     *
     * @return LatLng
     */
    public static function fromArray(array $latLng, ?Floor $floor): LatLng
    {
        return new LatLng($latLng['lat'], $latLng['lng'], $floor);
    }
}
