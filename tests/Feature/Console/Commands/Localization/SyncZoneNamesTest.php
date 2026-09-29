<?php

namespace Tests\Feature\Console\Commands\Localization;

use App\Console\Commands\Localization\Zone\SyncZoneNames;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Localization')]
final class SyncZoneNamesTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('normalizeFloorName_givenSameFloorWrittenDifferently_returnsSameName_dataProvider')]
    public function normalizeFloorName_givenSameFloorWrittenDifferently_returnsSameName(string $wowheadName, string $ksgName): void
    {
        // Arrange
        $command = new SyncZoneNames();

        // Act
        $normalizedWowheadName = $command->normalizeFloorName($wowheadName);
        $normalizedKsgName     = $command->normalizeFloorName($ksgName);

        // Assert
        $this->assertSame($normalizedKsgName, $normalizedWowheadName);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function normalizeFloorName_givenSameFloorWrittenDifferently_returnsSameName_dataProvider(): array
    {
        return [
            'dash'       => ["Vereesa's Repose - Upper", "Vereesa's Repose Upper"],
            'case'       => ["Atal'Dazar", "Atal'dazar"],
            'apostrophe' => ["Sylvanas's Quarters - Lower", 'Sylvanass Quarters Lower'],
        ];
    }

    #[Test]
    public function normalizeFloorName_givenDifferentFloors_returnsDifferentNames(): void
    {
        // Arrange
        $command = new SyncZoneNames();

        // Act
        $upper = $command->normalizeFloorName("Vereesa's Repose - Upper");
        $lower = $command->normalizeFloorName("Vereesa's Repose - Lower");

        // Assert
        $this->assertNotSame($upper, $lower);
    }
}
