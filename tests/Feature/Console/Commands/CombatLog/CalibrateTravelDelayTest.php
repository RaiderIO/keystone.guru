<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CalibrateTravelDelay')]
final class CalibrateTravelDelayTest extends PublicTestCase
{
    private const string FIXTURE_PATH = 'tests/Feature/Controller/Api/V1/APICombatLogController/Fixtures/TWW/tww_s1_ara_kara_city_of_echoes_3.json';

    #[Test]
    public function handle_givenCombatLogRouteJson_writesEveryTransitionToCsv(): void
    {
        // Arrange
        $csvFilePath = tempnam(sys_get_temp_dir(), 'calibrate_travel_delay_');

        try {
            // Act
            $exitCode = $this->artisan('combatlog:calibratetraveldelay', [
                'filePath' => base_path(self::FIXTURE_PATH),
                '--csv'    => $csvFilePath,
            ])->run();

            // Assert
            $this->assertSame(0, $exitCode);
            $lines = file($csvFilePath, FILE_IGNORE_NEW_LINES);
            $this->assertSame(
                'dungeon,pull,predicted_seconds,observed_seconds,exclusion,predicted_yards,previous_pull_ended_at,pull_started_at,file',
                $lines[0],
            );
            $this->assertGreaterThan(1, count($lines));
            $this->assertStringStartsWith('ara_karacityofechoes,2,', $lines[1]);
            $this->assertStringEndsWith(',tww_s1_ara_kara_city_of_echoes_3.json', $lines[1], 'Every row names the run it came from');
        } finally {
            unlink($csvFilePath);
        }
    }

    #[Test]
    public function handle_givenUnreadableCombatLogRoute_returnsFailure(): void
    {
        // Arrange
        $jsonFilePath = tempnam(sys_get_temp_dir(), 'calibrate_travel_delay_');
        file_put_contents($jsonFilePath, '{}');

        try {
            // Act
            $exitCode = $this->artisan('combatlog:calibratetraveldelay', ['filePath' => $jsonFilePath])->run();

            // Assert
            $this->assertSame(1, $exitCode);
        } finally {
            unlink($jsonFilePath);
        }
    }
}
