<?php

namespace App\Service\Telemetry\Dtos;

/**
 * One line on a telemetry chart: the bucketed values of a single (name, tag) combination.
 *
 * The three arrays are index-aligned - $buckets[$i] is the label of $averages[$i] and $maximums[$i].
 */
final readonly class TelemetrySeries
{
    /**
     * @param array<int, string> $buckets  Preformatted bucket labels, so the client needs no date adapter
     * @param array<int, float>  $averages
     * @param array<int, float>  $maximums
     */
    public function __construct(
        public string  $name,
        public ?string $tag,
        public array   $buckets,
        public array   $averages,
        public array   $maximums,
    ) {
    }

    /**
     * The name this series is shown under in a chart legend.
     */
    public function getLabel(): string
    {
        return $this->tag === null ? $this->name : sprintf('%s (%s)', $this->name, $this->tag);
    }
}
