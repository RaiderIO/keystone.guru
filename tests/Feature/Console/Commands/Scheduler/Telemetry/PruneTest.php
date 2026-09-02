<?php

namespace Tests\Feature\Console\Commands\Scheduler\Telemetry;

use App\Models\Telemetry\TelemetryMetric;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Scheduler')]
#[Group('Telemetry')]
final class PruneTest extends PublicTestCase
{
    #[Test]
    public function handle_givenOldAndRecentRecords_deletesOnlyOldRecords(): void
    {
        $retentionDays = config('keystoneguru.telemetry.retention_days');

        $oldTelemetryMetric    = null;
        $recentTelemetryMetric = null;

        try {
            // Arrange
            $oldTelemetryMetric = TelemetryMetric::factory()->create([
                'name'        => 'test:prunetest',
                'recorded_at' => now()->subDays($retentionDays + 10),
            ]);
            $recentTelemetryMetric = TelemetryMetric::factory()->create([
                'name'        => 'test:prunetest',
                'recorded_at' => now()->subDays(1),
            ]);

            // Act
            $exitCode = $this->artisan('telemetry:prune')->run();

            // Assert
            $this->assertSame(Command::SUCCESS, $exitCode);
            $this->assertNull(TelemetryMetric::query()->find($oldTelemetryMetric->id));
            $this->assertNotNull(TelemetryMetric::query()->find($recentTelemetryMetric->id));
        } finally {
            $oldTelemetryMetric?->delete();
            $recentTelemetryMetric?->delete();
            // The prune run itself records its own duration through trackTime()
            TelemetryMetric::query()
                ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
                ->where('name', 'telemetry:prune')
                ->delete();
        }
    }
}
