<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
class HeatmapDataFloorData implements Arrayable
{
    private int $floorId;

    /** @var Collection<int, HeatmapDataLatLng> */
    private Collection $latLngs;

    /** Set when this floor's cells lie on a grid of their own rather than the response's grid */
    private ?int $gridSizeX = null;

    private ?int $gridSizeY = null;

    /** Set when this floor's cells are sums of other cells, so the response's weight max does not apply to them */
    private ?int $weightMax = null;

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
     * @return Collection<int, HeatmapDataLatLng>
     */
    public function getLatLngs(): Collection
    {
        return $this->latLngs;
    }

    public function getGridSizeX(): ?int
    {
        return $this->gridSizeX;
    }

    public function getGridSizeY(): ?int
    {
        return $this->gridSizeY;
    }

    public function getWeightMax(): ?int
    {
        return $this->weightMax;
    }

    public function toArray(): array
    {
        return array_merge([
            'floor_id' => $this->floorId,
            'lat_lngs' => $this->latLngs->map(fn(HeatmapDataLatLng $latLng) => $latLng->toArray())->toArray(),
        ], array_filter([
            'grid_size_x' => $this->gridSizeX,
            'grid_size_y' => $this->gridSizeY,
            'weight_max'  => $this->weightMax,
        ], static fn(?int $value) => $value !== null));
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $model = new self();

        $model->floorId   = $data['floor_id'];
        $model->gridSizeX = $data['grid_size_x'] ?? null;
        $model->gridSizeY = $data['grid_size_y'] ?? null;
        $model->weightMax = $data['weight_max'] ?? null;
        $model->latLngs   = collect();
        foreach ($data['lat_lngs'] as $latLng) {
            $model->latLngs->push(
                HeatmapDataLatLng::fromArray($latLng),
            );
        }

        return $model;
    }
}
