<?php

namespace Tests\Feature\App\Console\Commands\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Telemetry\TelemetryMetric;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyResolutionRepositoryInterface;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogRouteEnemyResolution')]
final class PruneEnemyResolutionsTest extends PublicTestCase
{
    #[Test]
    public function handle_givenOldAndRecentResolutions_deletesOnlyTheOldOnes(): void
    {
        $retentionDays = (int)config('keystoneguru.enemy_resolution.retention_days');

        $oldResolution    = null;
        $recentResolution = null;

        try {
            // Arrange
            $oldResolution = CombatLogRouteEnemyResolution::factory()->create([
                'created_at' => now()->subDays($retentionDays + 1),
            ]);
            $recentResolution = CombatLogRouteEnemyResolution::factory()->create([
                'created_at' => now()->subDays(1),
            ]);

            // Act
            $exitCode = $this->artisan('combatlog:pruneenemyresolutions')->run();

            // Assert
            $this->assertSame(Command::SUCCESS, $exitCode);
            $this->assertNull(CombatLogRouteEnemyResolution::query()->find($oldResolution->id));
            $this->assertNotNull(CombatLogRouteEnemyResolution::query()->find($recentResolution->id));
        } finally {
            $oldResolution?->delete();
            $recentResolution?->delete();
            // The prune run itself records its own duration through trackTime()
            TelemetryMetric::query()
                ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
                ->where('name', 'combatlog:pruneenemyresolutions')
                ->delete();
        }
    }

    /**
     * The delete runs in batches, so more old rows than fit in one batch must still all go.
     */
    #[Test]
    public function deleteOlderThan_givenMoreOldResolutionsThanTheBatchSize_deletesThemAll(): void
    {
        $cutoff = now()->subDays(14);

        $oldResolutions   = null;
        $recentResolution = null;

        try {
            // Arrange
            $oldResolutions = CombatLogRouteEnemyResolution::factory()->count(3)->create([
                'created_at' => $cutoff->copy()->subDay(),
            ]);
            $recentResolution = CombatLogRouteEnemyResolution::factory()->create([
                'created_at' => $cutoff->copy()->addDay(),
            ]);

            // Act
            $deleted = app(CombatLogRouteEnemyResolutionRepositoryInterface::class)->deleteOlderThan($cutoff, 1);

            // Assert
            $this->assertGreaterThanOrEqual(3, $deleted);
            $this->assertSame(
                0,
                CombatLogRouteEnemyResolution::query()->whereKey($oldResolutions->pluck('id'))->count(),
            );
            $this->assertNotNull(CombatLogRouteEnemyResolution::query()->find($recentResolution->id));
        } finally {
            if ($oldResolutions !== null) {
                CombatLogRouteEnemyResolution::query()->whereKey($oldResolutions->pluck('id'))->delete();
            }

            $recentResolution?->delete();
        }
    }
}
