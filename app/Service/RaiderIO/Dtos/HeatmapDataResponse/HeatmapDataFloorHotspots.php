<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
class HeatmapDataFloorHotspots implements Arrayable
{
    private int $floorId;

    /** @var Collection<int, HeatmapDataHotspot> */
    private Collection $hotspots;

    private function __construct()
    {
    }

    public function getFloorId(): int
    {
        return $this->floorId;
    }

    /**
     * @return Collection<int, HeatmapDataHotspot>
     */
    public function getHotspots(): Collection
    {
        return $this->hotspots;
    }

    public function toArray(): array
    {
        return [
            'floor_id' => $this->floorId,
            'hotspots' => $this->hotspots->map(fn(HeatmapDataHotspot $hotspot) => $hotspot->toArray())->values()->toArray(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $instance          = new self();
        $instance->floorId = $data['floor_id'];
        /** @var list<array<string, mixed>> $hotspots */
        $hotspots           = $data['hotspots'];
        $instance->hotspots = collect($hotspots)->map(fn(array $hotspot) => HeatmapDataHotspot::fromArray($hotspot))->values();

        return $instance;
    }
}
