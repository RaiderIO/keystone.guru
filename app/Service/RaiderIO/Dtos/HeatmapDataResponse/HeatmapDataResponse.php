<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class HeatmapDataResponse implements Arrayable
{
    private Collection $data;

    private int $runCount;

    private function __construct()
    {

    }

    public function getData(): Collection
    {
        return $this->data;
    }

    public function getRunCount(): int
    {
        return $this->runCount;
    }

    public function toArray() : array
    {
        return [
            'data' => $this->data->map(
                fn(HeatmapDataFloorData $floorData) => $floorData->toArray()
            )->values()->toArray(),
            'run_count' => $this->runCount,
        ];
    }

    public static function fromArray(array $response): HeatmapDataResponse
    {
        $result = new self();

        $result->data = collect();
        foreach ($response['data'] as $floorData) {
            $result->data->push(
                HeatmapDataFloorData::fromArray($floorData)
            );
        }
        $result->runCount = $response['run_count'];

        return $result;
    }
}
