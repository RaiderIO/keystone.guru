<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use App\Models\CombatLog\CombatLogEventDataType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
class HeatmapDataResponse implements Arrayable
{
    /** @var Collection<int, HeatmapDataFloorData> */
    private Collection $data;
    private CombatLogEventDataType $dataType;
    private int $runCount;
    private int $weightMax;
    private int $gridSizeX;
    private int $gridSizeY;
    private ?string $url = null;

    /** @var Collection<int, HeatmapDataFloorHotspots>|null Null when the source sent no hotspots at all */
    private ?Collection $hotspots = null;

    private function __construct()
    {
    }

    /**
     * @return Collection<int, HeatmapDataFloorData>
     */
    public function getData(): Collection
    {
        return $this->data;
    }

    public function getDataType(): CombatLogEventDataType
    {
        return $this->dataType;
    }

    public function getRunCount(): int
    {
        return $this->runCount;
    }

    public function getWeightMax(): int
    {
        return $this->weightMax;
    }

    public function getGridSizeX(): int
    {
        return $this->gridSizeX;
    }

    public function getGridSizeY(): int
    {
        return $this->gridSizeY;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return Collection<int, HeatmapDataFloorHotspots>|null
     */
    public function getHotspots(): ?Collection
    {
        return $this->hotspots;
    }

    public function toArray(): array
    {
        $result = [
            'data' => $this->data->map(
                fn(HeatmapDataFloorData $floorData) => $floorData->toArray(),
            )->values()->toArray(),
            'data_type'   => $this->dataType->value,
            'run_count'   => $this->runCount,
            'weight_max'  => $this->weightMax,
            'grid_size_x' => $this->gridSizeX,
            'grid_size_y' => $this->gridSizeY,
            'url'         => $this->url,
        ];

        if ($this->hotspots !== null) {
            $result['hotspots'] = $this->hotspots->map(
                fn(HeatmapDataFloorHotspots $floorHotspots) => $floorHotspots->toArray(),
            )->values()->toArray();
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $response
     */
    public static function fromArray(array $response): HeatmapDataResponse
    {
        $result = new self();

        $result->data = collect();
        foreach ($response['data'] as $floorData) {
            $result->data->push(
                HeatmapDataFloorData::fromArray($floorData),
            );
        }

        $result->dataType  = $response['data_type'];
        $result->runCount  = $response['run_count'];
        $result->weightMax = $response['weight_max'];
        $result->gridSizeX = $response['grid_size_x'] ?? config('keystoneguru.heatmap.service.data.player.size_x');
        $result->gridSizeY = $response['grid_size_y'] ?? config('keystoneguru.heatmap.service.data.player.size_y');
        if (isset($response['url'])) {
            $result->url = $response['url'];
        }

        if (isset($response['hotspots'])) {
            /** @var list<array<string, mixed>> $hotspots */
            $hotspots         = $response['hotspots'];
            $result->hotspots = collect($hotspots)->map(
                fn(array $floorHotspots) => HeatmapDataFloorHotspots::fromArray($floorHotspots),
            )->values();
        }

        return $result;
    }
}
