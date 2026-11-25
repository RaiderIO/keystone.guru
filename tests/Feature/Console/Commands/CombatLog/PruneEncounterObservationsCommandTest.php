<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Console\Commands\CombatLog\PruneEncounterObservationsCommand;
use App\Models\CombatLog\CombatLogEncounterObservation;
use App\Models\Telemetry\TelemetryMetric;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
#[Group('EncounterMechanics')]
final class PruneEncounterObservationsCommandTest extends PublicTestCase
{
    /** @var array<int, int> */
    private array $createdIds = [];

    private int $telemetryMetricMaxIdBefore;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->telemetryMetricMaxIdBefore = (int)TelemetryMetric::query()->max('id');
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogEncounterObservation::query()->whereIn('id', $this->createdIds)->delete();

            // Every run records its own duration through trackTime()
            TelemetryMetric::query()
                ->where('id', '>', $this->telemetryMetricMaxIdBefore)
                ->where('measurement', TelemetryMetric::MEASUREMENT_SCHEDULER)
                ->where('name', 'combatlog:pruneencounterobservations')
                ->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenObservationsOnBothSidesOfTheDefaultRetention_deletesOnlyTheOlderOnes(): void
    {
        // Arrange
        $old    = $this->createObservation(Carbon::now()->subDays(61));
        $recent = $this->createObservation(Carbon::now()->subDays(59));

        // Act
        $this->artisan(PruneEncounterObservationsCommand::class)->assertSuccessful();

        // Assert
        $this->assertFalse(CombatLogEncounterObservation::query()->whereKey($old->id)->exists());
        $this->assertTrue(CombatLogEncounterObservation::query()->whereKey($recent->id)->exists());
    }

    #[Test]
    public function handle_givenAConfiguredRetention_deletesObservationsOlderThanIt(): void
    {
        // Arrange
        config(['keystoneguru.combat_log_encounter_observations.retention_days' => 10]);
        $old    = $this->createObservation(Carbon::now()->subDays(11));
        $recent = $this->createObservation(Carbon::now()->subDays(9));

        // Act
        $this->artisan(PruneEncounterObservationsCommand::class)->assertSuccessful();

        // Assert
        $this->assertFalse(CombatLogEncounterObservation::query()->whereKey($old->id)->exists());
        $this->assertTrue(CombatLogEncounterObservation::query()->whereKey($recent->id)->exists());
    }

    private function createObservation(Carbon $createdAt): CombatLogEncounterObservation
    {
        $observation = CombatLogEncounterObservation::factory()->create([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $this->createdIds[] = $observation->id;

        return $observation;
    }
}
