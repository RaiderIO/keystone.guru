<?php

namespace App\Service\RaiderIO\Dtos\HeatmapDataResponse;

use App\Models\CombatLog\CombatLogEventDataType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class HeatmapDataResponse implements Arrayable
{
    private Collection             $data;
    private CombatLogEventDataType $dataType;
    private int                    $runCount;
    private ?string                $url = null;

    private function __construct()
    {

    }

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function toArray(): array
    {
        return array_filter([
            'data'      => $this->data->map(
                fn(HeatmapDataFloorData $floorData) => $floorData->toArray()
            )->values()->toArray(),
            'data_type' => $this->dataType->value,
            'run_count' => $this->runCount,
            'url'       => $this->url,
        ]);
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

        $result->dataType = $response['data_type'];
        $result->runCount = $response['run_count'];
        if (isset($response['url'])) {
            $result->url = $response['url'];
        }

        return $result;
    }
}
