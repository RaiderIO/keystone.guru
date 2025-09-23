<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use Illuminate\Contracts\Support\Arrayable;

class HeatmapDataLatLng implements Arrayable
{
    private float $lat;
    private float $lng;
    private int $weight;

    private function __construct()
    {
    }

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLng(): float
    {
        return $this->lng;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function toArray(): array
    {
        return [
            'lat'    => $this->lat,
            'lng'    => $this->lng,
            'weight' => $this->weight,
        ];
    }

    public static function fromArray(array $data): self
    {
        $instance         = new self();
        $instance->lat    = $data['lat'];
        $instance->lng    = $data['lng'];
        $instance->weight = $data['weight'];

        return $instance;
    }
}
