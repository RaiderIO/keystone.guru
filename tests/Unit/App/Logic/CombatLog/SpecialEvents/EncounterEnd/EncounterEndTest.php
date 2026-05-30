<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\EncounterEnd;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\EncounterEndBuilder;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V20\EncounterEndV20;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V9\EncounterEndV9;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class EncounterEndTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('EncounterEnd')]
    #[DataProvider('parseEvent_givenEncounterEndEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenEncounterEndEvent_returnsCorrectValues(
        string $encounterEndEvent,
        int    $expectedEncounterId,
        string $expectedEncounterName,
        int    $expectedDifficultyId,
        int    $expectedGroupSize,
        int    $expectedSuccess,
        int    $expectedFightTimeMS,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($encounterEndEvent);

        // Act
        /** @var EncounterEndV20 $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(EncounterEndV20::class, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedEncounterId, $result->getEncounterId());
        Assert::assertEquals($expectedEncounterName, $result->getEncounterName());
        Assert::assertEquals($expectedDifficultyId, $result->getDifficultyId());
        Assert::assertEquals($expectedGroupSize, $result->getGroupSize());
        Assert::assertEquals($expectedSuccess, $result->getSuccess());
        Assert::assertEquals($expectedFightTimeMS, $result->getFightTimeMS());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('EncounterEnd')]
    public function create_givenClassicVersion_returnsEncounterEndV9(): void
    {
        // Act
        // V9 format: ENCOUNTER_END,665,"Gehennas",226,20,1 — 5 params (no fightTimeMS)
        $result = EncounterEndBuilder::create(
            CombatLogVersion::CLASSIC,
            Carbon::now(),
            'ENCOUNTER_END',
            [665, 'Gehennas', 226, 20, 1],
            '',
        );

        // Assert
        Assert::assertInstanceOf(EncounterEndV9::class, $result);
    }

    public static function parseEvent_givenEncounterEndEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'overgrown-ancient-success' => [
                '3/25/2026 10:41:20.1521  ENCOUNTER_END,2563,"Overgrown Ancient",8,5,1,72137',
                2563,
                'Overgrown Ancient',
                8,
                5,
                1,
                72137,
            ],
            'crawth-success' => [
                '3/25/2026 10:46:48.8171  ENCOUNTER_END,2564,"Crawth",8,5,1,133503',
                2564,
                'Crawth',
                8,
                5,
                1,
                133503,
            ],
        ];
    }
}
