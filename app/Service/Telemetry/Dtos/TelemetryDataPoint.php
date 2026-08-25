<?php

namespace App\Service\Telemetry\Dtos;

use Illuminate\Support\Carbon;

/**
 * One data point destined for the telemetry_metrics table.
 */
final readonly class TelemetryDataPoint
{
    public function __construct(
        public string  $measurement,
        public string  $name,
        public float   $value,
        public ?string $tag = null,
        public ?Carbon $recordedAt = null,
        public bool    $success = true,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(): array
    {
        return [
            'measurement' => $this->measurement,
            'name'        => $this->name,
            'tag'         => $this->tag,
            'value'       => $this->value,
            'success'     => $this->success,
            'recorded_at' => $this->recordedAt ?? now(),
        ];
    }
}
