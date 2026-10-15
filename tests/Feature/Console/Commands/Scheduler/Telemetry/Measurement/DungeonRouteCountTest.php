<?php

namespace Tests\Feature\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Console\Commands\Scheduler\Telemetry\Measurement\DungeonRouteCount;
use App\Models\PublishedState;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Scheduler')]
#[Group('Telemetry')]
final class DungeonRouteCountTest extends PublicTestCase
{
    #[Test]
    public function getDataPoints_givenSeededPublishedStates_returnsOneDataPointPerPublishedStateKey(): void
    {
        // Arrange
        $expectedNames = array_merge(['all', 'temporary'], array_map(
            static fn(string $publishedStateKey): string => sprintf('published_%s', $publishedStateKey),
            array_keys(PublishedState::ALL),
        ));

        // Act
        $dataPoints = (new DungeonRouteCount())->getDataPoints();

        // Assert
        $this->assertSame($expectedNames, array_map(
            static fn(TelemetryDataPoint $dataPoint): string => $dataPoint->name,
            $dataPoints,
        ));
    }
}
