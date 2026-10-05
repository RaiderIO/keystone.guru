<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\CombatLogVersion;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\SpecialEvents\Interfaces\HasCombatLogVersionInterface;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use Exception;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogVersion')]
final class CombatLogVersionTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('getVersionLong_givenCombatLogVersionEvent_returnsCorrectVersionLong_DataProvider')]
    public function getVersionLong_givenCombatLogVersionEvent_returnsCorrectVersionLong(
        string $rawEvent,
        int    $expectedVersionLong,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($rawEvent);

        // Act
        /** @var CombatLogVersionEvent $result */
        $result = $combatLogEntry->parseEvent([], $expectedVersionLong);

        // Assert
        Assert::assertInstanceOf(HasCombatLogVersionInterface::class, $result);
        Assert::assertEquals($expectedVersionLong, $result->getVersionLong());
    }

    /**
     * @return array<string, list<int|string>>
     */
    public static function getVersionLong_givenCombatLogVersionEvent_returnsCorrectVersionLong_DataProvider(): array
    {
        return [
            'retail-10-1-0' => [
                '5/15 21:20:10.941  COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,10.1.0,PROJECT_ID,1',
                CombatLogVersion::RETAIL_10_1_0,
            ],
            'retail-12-0-5' => [
                '5/31/2026 22:00:00.0000  COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1',
                CombatLogVersion::RETAIL_12_0_5,
            ],
            'retail-12-0-7' => [
                '7/19/2026 19:31:59.774-6  COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.7,PROJECT_ID,1',
                CombatLogVersion::RETAIL_12_0_7,
            ],
            'retail-12-1-0' => [
                '7/19/2026 19:31:59.774-6  COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.1.0,PROJECT_ID,1',
                CombatLogVersion::RETAIL_12_1_0,
            ],
        ];
    }

    #[Test]
    public function setParameters_givenABuildVersionThatIsNotRegistered_throwsException(): void
    {
        // Arrange
        $parameters = ['22', 'ADVANCED_LOG_ENABLED', '1', 'BUILD_VERSION', '9.9.9', 'PROJECT_ID', '1'];

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to find combat log version 22009009009!');

        // Act
        new CombatLogVersionEvent(
            CombatLogVersion::RETAIL_12_0_5,
            Carbon::parse('2026-05-31 22:00:00'),
            SpecialEvent::SPECIAL_EVENT_COMBAT_LOG_VERSION,
            $parameters,
            '',
        );
    }

    #[Test]
    #[DataProvider('parseEvent_givenAnAdvancedLogEnabledFlag_returnsWhetherAdvancedLoggingIsEnabled_DataProvider')]
    public function parseEvent_givenAnAdvancedLogEnabledFlag_returnsWhetherAdvancedLoggingIsEnabled(
        string $rawEvent,
        bool   $expectedAdvancedLogEnabled,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($rawEvent);

        // Act
        /** @var CombatLogVersionEvent $result */
        $result = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_5);

        // Assert
        Assert::assertSame($expectedAdvancedLogEnabled, $result->isAdvancedLogEnabled());
        Assert::assertSame(22, $result->getVersion());
        Assert::assertSame('12.0.5', $result->getBuildVersion());
        Assert::assertSame(1, $result->getProjectID());
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function parseEvent_givenAnAdvancedLogEnabledFlag_returnsWhetherAdvancedLoggingIsEnabled_DataProvider(): array
    {
        return [
            'enabled' => [
                '5/31/2026 22:00:00.0000  COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.0.5,PROJECT_ID,1',
                true,
            ],
            'disabled' => [
                '5/31/2026 22:00:00.0000  COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,0,BUILD_VERSION,12.0.5,PROJECT_ID,1',
                false,
            ],
        ];
    }
}
