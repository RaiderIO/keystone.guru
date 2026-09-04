<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\Telemetry\TelemetryMetric;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsTelemetryControllerTest extends PublicTestCase
{
    private const int ADMIN_USER_ID     = 1;
    private const int NON_ADMIN_USER_ID = 3;

    /**
     * Every assertion is scoped to what this test created - the test database is persistent and shared, so
     * absolute catalog contents say nothing.
     *
     * @var array<int, int>
     */
    private array $createdMetricIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(self::ADMIN_USER_ID));
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            TelemetryMetric::query()->whereIn('id', $this->createdMetricIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function index_givenAdminUser_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.telemetry.view'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('admin.tools.telemetry');
        $response->assertViewHas('range', '24h');
        $response->assertViewHas('schedulerCommands');
        $response->assertViewHas('gaugeMeasurements');
    }

    #[Test]
    public function index_givenNonAdminUser_returnsForbidden(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.telemetry.view'));

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function index_givenUnsupportedRange_failsValidation(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.telemetry.view', ['range' => '3h']));

        // Assert
        $response->assertSessionHasErrors('range');
    }

    #[Test]
    public function index_givenGaugeWithoutTranslation_listsItUnderItsOwnKey(): void
    {
        // Arrange - a measurement added to scheduler:telemetry later must graph itself without dashboard wiring
        $measurement = $this->createMeasurement();
        $this->recordDataPoint($measurement, 'size', 12.0, tag: 'db0');

        // Act
        $response = $this->get(route('admin.tools.telemetry.view'));

        // Assert
        $response->assertOk();
        $this->assertSame($measurement, $response->viewData('gaugeMeasurements')[$measurement] ?? null);
    }

    #[Test]
    public function index_givenSchedulerRun_listsCommandButNotAsGauge(): void
    {
        // Arrange
        $command = $this->createMeasurement();
        $this->recordDataPoint(TelemetryMetric::MEASUREMENT_SCHEDULER, $command, 1234.0);

        // Act
        $response = $this->get(route('admin.tools.telemetry.view'));

        // Assert
        $response->assertOk();
        $this->assertContains($command, $response->viewData('schedulerCommands'));
        $this->assertArrayNotHasKey(TelemetryMetric::MEASUREMENT_SCHEDULER, $response->viewData('gaugeMeasurements'));
    }

    #[Test]
    public function index_givenDataPointOutsideRange_omitsItFromCatalog(): void
    {
        // Arrange
        $measurement = $this->createMeasurement();
        $this->recordDataPoint($measurement, 'size', 1.0, recordedAt: Carbon::now()->subDays(2));

        // Act
        $withinDay  = $this->get(route('admin.tools.telemetry.view', ['range' => '24h']));
        $withinWeek = $this->get(route('admin.tools.telemetry.view', ['range' => '7d']));

        // Assert
        $this->assertArrayNotHasKey($measurement, $withinDay->viewData('gaugeMeasurements'));
        $this->assertArrayHasKey($measurement, $withinWeek->viewData('gaugeMeasurements'));
    }

    #[Test]
    public function data_givenGaugeMeasurement_returnsOneDatasetPerNameAndTag(): void
    {
        // Arrange
        $measurement = $this->createMeasurement();
        $this->recordDataPoint($measurement, 'keys', 10.0, tag: 'db0');
        $this->recordDataPoint($measurement, 'keys', 20.0, tag: 'db1');

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', ['measurement' => $measurement, 'range' => '24h']));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('rollup', false);
        $this->assertSame(
            ['keys (db0)', 'keys (db1)'],
            array_column($response->json('datasets'), 'label'),
        );
    }

    #[Test]
    public function data_givenNameFilter_returnsOnlyThatName(): void
    {
        // Arrange
        $measurement = $this->createMeasurement();
        $this->recordDataPoint($measurement, 'wanted', 10.0);
        $this->recordDataPoint($measurement, 'unwanted', 20.0);

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', [
            'measurement' => $measurement,
            'name'        => 'wanted',
            'range'       => '24h',
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame(['wanted'], array_column($response->json('datasets'), 'label'));
    }

    #[Test]
    public function data_givenRolledUpRange_returnsAverageAndMaximumOfTheBucket(): void
    {
        // Arrange - two samples in the same hour, so the bucket has a meaningful average and maximum
        $measurement = $this->createMeasurement();
        $recordedAt  = Carbon::now()->subDays(2)->startOfHour()->addMinutes(5);
        $this->recordDataPoint($measurement, 'size', 10.0, recordedAt: $recordedAt);
        $this->recordDataPoint($measurement, 'size', 30.0, recordedAt: $recordedAt->copy()->addMinutes(10));

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', ['measurement' => $measurement, 'range' => '7d']));

        // Assert
        $response->assertOk();
        $response->assertJsonPath('rollup', true);
        $this->assertEquals([20.0], $response->json('datasets.0.average'));
        $this->assertEquals([30.0], $response->json('datasets.0.maximum'));
        $this->assertSame([$recordedAt->format('Y-m-d H:00')], $response->json('labels'));
    }

    #[Test]
    public function data_givenSeriesStartingLate_padsItWithNullsOntoTheSharedAxis(): void
    {
        // Arrange - a gauge that only started being recorded halfway must not shift its line sideways
        $measurement = $this->createMeasurement();
        $early       = Carbon::now()->subHours(3)->startOfMinute();
        $late        = Carbon::now()->subHours(1)->startOfMinute();
        $this->recordDataPoint($measurement, 'old', 1.0, recordedAt: $early);
        $this->recordDataPoint($measurement, 'old', 2.0, recordedAt: $late);
        $this->recordDataPoint($measurement, 'new', 3.0, recordedAt: $late);

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', ['measurement' => $measurement, 'range' => '24h']));

        // Assert
        $response->assertOk();
        $this->assertSame([$early->format('Y-m-d H:i'), $late->format('Y-m-d H:i')], $response->json('labels'));

        $averagesByLabel = array_column($response->json('datasets'), 'average', 'label');
        $this->assertEquals([1.0, 2.0], $averagesByLabel['old']);
        $this->assertEquals([null, 3.0], $averagesByLabel['new']);
    }

    #[Test]
    public function data_givenFailedSchedulerRun_returnsItsBucketAsAFailureLabel(): void
    {
        // Arrange
        $command  = $this->createMeasurement();
        $failedAt = Carbon::now()->subHours(2)->startOfMinute();
        $this->recordDataPoint(TelemetryMetric::MEASUREMENT_SCHEDULER, $command, 500.0, recordedAt: Carbon::now()->subHours(3)->startOfMinute());
        $this->recordDataPoint(TelemetryMetric::MEASUREMENT_SCHEDULER, $command, 900.0, recordedAt: $failedAt, success: false);

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', [
            'measurement' => TelemetryMetric::MEASUREMENT_SCHEDULER,
            'name'        => $command,
            'range'       => '24h',
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame([$failedAt->format('Y-m-d H:i')], $response->json('failureLabels'));
    }

    #[Test]
    public function data_givenGaugeMeasurement_neverReturnsFailureLabels(): void
    {
        // Arrange - only the scheduler measurement records a success flag; a gauge's is meaningless
        $measurement = $this->createMeasurement();
        $this->recordDataPoint($measurement, 'size', 1.0, success: false);

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', ['measurement' => $measurement, 'range' => '24h']));

        // Assert
        $response->assertOk();
        $this->assertSame([], $response->json('failureLabels'));
    }

    #[Test]
    public function data_givenMeasurementWithoutDataInRange_returnsEmptySeries(): void
    {
        // Arrange
        $measurement = $this->createMeasurement();
        $this->recordDataPoint($measurement, 'size', 1.0, recordedAt: Carbon::now()->subDays(2));

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', ['measurement' => $measurement, 'range' => '24h']));

        // Assert
        $response->assertOk();
        $this->assertSame([], $response->json('labels'));
        $this->assertSame([], $response->json('datasets'));
    }

    #[Test]
    public function data_givenNoMeasurement_failsValidation(): void
    {
        // Arrange

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', ['range' => '24h']));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('measurement');
    }

    #[Test]
    public function data_givenNonAdminUser_returnsForbidden(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        // Act
        $response = $this->getJson(route('admin.tools.telemetry.data', ['measurement' => TelemetryMetric::MEASUREMENT_REDIS]));

        // Assert
        $response->assertForbidden();
    }

    /**
     * A measurement name no other test or seeded row can collide with.
     */
    private function createMeasurement(): string
    {
        return sprintf('test_%s', bin2hex(random_bytes(8)));
    }

    private function recordDataPoint(
        string  $measurement,
        string  $name,
        float   $value,
        ?string $tag = null,
        ?Carbon $recordedAt = null,
        bool    $success = true,
    ): void {
        $this->createdMetricIds[] = TelemetryMetric::factory()->create([
            'measurement' => $measurement,
            'name'        => $name,
            'tag'         => $tag,
            'value'       => $value,
            'success'     => $success,
            'recorded_at' => $recordedAt ?? Carbon::now()->subMinutes(5),
        ])->id;
    }
}
