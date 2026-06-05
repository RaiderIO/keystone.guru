<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\RioLogVersion;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\RioLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('RioLogVersion')]
final class RioLogVersionTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('parseEvent_givenRioLogVersionEvent_returnsCorrectValues_DataProvider')]
    public function parseEvent_givenRioLogVersionEvent_returnsCorrectValues(
        string $rioLogVersionEvent,
        int    $expectedRioLogVersion,
        string $expectedSegmentType,
        string $expectedAppVersion,
        int    $expectedProcessorVersion,
        string $expectedPlatform,
        int    $expectedInstanceID,
        int    $expectedDungeonID,
        ?int   $expectedEncounterID,
        int    $expectedSegmentID,
        string $expectedCorrelationID,
        int    $expectedChallengeModeStartedAt,
        ?int   $expectedEncounterStartedAt,
        string $expectedType,
        string $expectedClientSessionID,
        int    $expectedEmbeddedCombatLogVersion,
        bool   $expectedAdvancedLogEnabled,
        string $expectedBuildVersion,
        int    $expectedProjectID,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($rioLogVersionEvent);

        // Act
        /** @var RioLogVersion $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_5);

        // Assert
        Assert::assertInstanceOf(RioLogVersion::class, $combatLogEntry->getParsedEvent());
        Assert::assertEquals($expectedRioLogVersion, $result->getRioLogVersion());
        Assert::assertEquals($expectedSegmentType, $result->getSegmentType());
        Assert::assertEquals($expectedAppVersion, $result->getAppVersion());
        Assert::assertEquals($expectedProcessorVersion, $result->getProcessorVersion());
        Assert::assertEquals($expectedPlatform, $result->getPlatform());
        Assert::assertEquals($expectedInstanceID, $result->getInstanceID());
        Assert::assertEquals($expectedDungeonID, $result->getDungeonID());
        Assert::assertEquals($expectedEncounterID, $result->getEncounterID());
        Assert::assertEquals($expectedSegmentID, $result->getSegmentID());
        Assert::assertEquals($expectedCorrelationID, $result->getCorrelationID());
        Assert::assertEquals($expectedChallengeModeStartedAt, $result->getChallengeModeStartedAt());
        Assert::assertEquals($expectedEncounterStartedAt, $result->getEncounterStartedAt());
        Assert::assertEquals($expectedType, $result->getType());
        Assert::assertEquals($expectedClientSessionID, $result->getClientSessionID());
        Assert::assertEquals($expectedEmbeddedCombatLogVersion, $result->getEmbeddedCombatLogVersion());
        Assert::assertEquals($expectedAdvancedLogEnabled, $result->isAdvancedLogEnabled());
        Assert::assertEquals($expectedBuildVersion, $result->getBuildVersion());
        Assert::assertEquals($expectedProjectID, $result->getProjectID());
    }

    #[Test]
    public function getVersionLong_givenRioLogVersionEvent_returnsRetail12_0_5VersionLong(): void
    {
        // Arrange
        $combatLogEntry = new CombatLogEntry('5/31/2026 22:14:06.7292  RIO_LOG_VERSION,1,SEGMENT_TYPE,mplus_trash,APP_VERSION,4.11.2,PROCESSOR_VERSION,1,PLATFORM,win32,INSTANCE_ID,2811,DUNGEON_ID,558,SEGMENT_ID,1,CORRELATION_ID,2811-10-158-9-10-ae253ffcf1bff14c79f9bd407eb657e49852d3ce529730155977c8b809ba4121,CHALLENGE_MODE_STARTED_AT,1780258447159,TYPE,trash,CLIENT_SESSION_ID,ba6e8ff8-2496-4280-9aca-42ae0d94bb56,COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1');

        // Act
        /** @var RioLogVersion $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_5);

        // Assert
        Assert::assertEquals(CombatLogVersion::RETAIL_12_0_5, $result->getVersionLong());
    }

    public static function parseEvent_givenRioLogVersionEvent_returnsCorrectValues_DataProvider(): array
    {
        return [
            'mplus-trash-win32' => [
                '5/31/2026 22:14:06.7292  RIO_LOG_VERSION,1,SEGMENT_TYPE,mplus_trash,APP_VERSION,4.11.2,PROCESSOR_VERSION,1,PLATFORM,win32,INSTANCE_ID,2811,DUNGEON_ID,558,SEGMENT_ID,1,CORRELATION_ID,2811-10-158-9-10-ae253ffcf1bff14c79f9bd407eb657e49852d3ce529730155977c8b809ba4121,CHALLENGE_MODE_STARTED_AT,1780258447159,TYPE,trash,CLIENT_SESSION_ID,ba6e8ff8-2496-4280-9aca-42ae0d94bb56,COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1',
                1,
                'mplus_trash',
                '4.11.2',
                1,
                'win32',
                2811,
                558,
                null,
                1,
                '2811-10-158-9-10-ae253ffcf1bff14c79f9bd407eb657e49852d3ce529730155977c8b809ba4121',
                1780258447159,
                null,
                'trash',
                'ba6e8ff8-2496-4280-9aca-42ae0d94bb56',
                22,
                true,
                '12.0.5',
                1,
            ],
            'mplus-boss-win32' => [
                '5/31/2026 22:17:12.4042  RIO_LOG_VERSION,1,SEGMENT_TYPE,mplus_boss,APP_VERSION,4.11.2,PROCESSOR_VERSION,1,PLATFORM,win32,INSTANCE_ID,2811,DUNGEON_ID,558,ENCOUNTER_ID,3071,SEGMENT_ID,2,CORRELATION_ID,2811-10-158-9-10-ae253ffcf1bff14c79f9bd407eb657e49852d3ce529730155977c8b809ba4121,CHALLENGE_MODE_STARTED_AT,1780258447159,ENCOUNTER_STARTED_AT,1780258632834,TYPE,boss,CLIENT_SESSION_ID,ba6e8ff8-2496-4280-9aca-42ae0d94bb56,COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1',
                1,
                'mplus_boss',
                '4.11.2',
                1,
                'win32',
                2811,
                558,
                3071,
                2,
                '2811-10-158-9-10-ae253ffcf1bff14c79f9bd407eb657e49852d3ce529730155977c8b809ba4121',
                1780258447159,
                1780258632834,
                'boss',
                'ba6e8ff8-2496-4280-9aca-42ae0d94bb56',
                22,
                true,
                '12.0.5',
                1,
            ],
        ];
    }
}
