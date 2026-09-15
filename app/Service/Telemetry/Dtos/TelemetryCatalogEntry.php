<?php

namespace App\Service\Telemetry\Dtos;

/**
 * One (measurement, name, tag) combination that has data in the selected range. The dashboard builds its chart
 * list from these, so a measurement added to `scheduler:telemetry` later shows up without any dashboard wiring.
 */
final readonly class TelemetryCatalogEntry
{
    public function __construct(
        public string  $measurement,
        public string  $name,
        public ?string $tag = null,
    ) {
    }
}
