<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class HeatmapDataFloorData implements Arrayable
{
    private int $floorId;

    /** @var Collection<HeatmapDataLatLng> */
    private Collection $latLngs;

    private function __construct()
    {

    }

    /**
     * @return int
     */
    public function getFloorId(): int
    {
        return $this->floorId;
    }

    /**
     * @return Collection<HeatmapDataLatLng>
     */
    public function getLatLngs(): Collection
    {
        return $this->latLngs;
    }

    public function toArray(): array
    {
        return [
            'floor_id' => $this->floorId,
            'lat_lngs' => $this->latLngs->map(fn(HeatmapDataLatLng $latLng) => $latLng->toArray())->toArray(),
        ];
    }

    public static function fromArray(array $data): self
    {
        $model = new self();

        $model->floorId = $data['floor_id'];
        $model->latLngs = collect();
        foreach ($data['lat_lngs'] as $latLng) {
            $model->latLngs->push(
                HeatmapDataLatLng::fromArray($latLng)
            );
        }

        return $model;
    }
}
