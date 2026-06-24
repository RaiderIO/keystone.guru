<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\EncounterStart;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\EncounterStartBuilder;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\Versions\V20\EncounterStartV20;
use App\Logic\CombatLog\SpecialEvents\EncounterStart\Versions\V9\EncounterStartV9;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class EncounterStartTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('EncounterStart')]
    #[DataProvider('parseEvent_givenEncounterStartEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenEncounterStartEvent_returnsCorrectValues(
        string $encounterStartEvent,
        int    $expectedEncounterId,
        string $expectedEncounterName,
        int    $expectedDifficultyId,
        int    $expectedGroupSize,
        int    $expectedInstanceID,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($encounterStartEvent);

        // Act
        /** @var EncounterStartV20 $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(EncounterStartV20::class, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedEncounterId, $result->getEncounterId());
        Assert::assertEquals($expectedEncounterName, $result->getEncounterName());
        Assert::assertEquals($expectedDifficultyId, $result->getDifficultyId());
        Assert::assertEquals($expectedGroupSize, $result->getGroupSize());
        Assert::assertEquals($expectedInstanceID, $result->getInstanceID());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('EncounterStart')]
    public function create_givenClassicVersion_returnsEncounterStartV9(): void
    {
        // Act
        // V9 format: ENCOUNTER_START,665,"Gehennas",226,20,409,2 — 6 params (5 used + 1 extra Classic field)
        $result = EncounterStartBuilder::create(
            CombatLogVersion::CLASSIC,
            Carbon::now(),
            'ENCOUNTER_START',
            [665, 'Gehennas', 226, 20, 409, 2],
            '',
        );

        // Assert
        Assert::assertInstanceOf(EncounterStartV9::class, $result);
    }

    /**
     * @return array<string, list<int|string>>
     */
    public static function parseEvent_givenEncounterStartEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'overgrown-ancient' => [
                '3/25/2026 10:40:08.0111  ENCOUNTER_START,2563,"Overgrown Ancient",8,5,2526',
                2563,
                'Overgrown Ancient',
                8,
                5,
                2526,
            ],
            'crawth' => [
                '3/25/2026 10:44:35.3051  ENCOUNTER_START,2564,"Crawth",8,5,2526',
                2564,
                'Crawth',
                8,
                5,
                2526,
            ],
        ];
    }
}
