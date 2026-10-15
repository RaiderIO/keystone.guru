<?php

namespace Tests\Feature\App\Service\Season\SeasonAffixGroupService;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonAffixGroupService;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockException;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonAffixGroupService')]
#[Group('GetAffixGroupIndexAt')]
final class GetAffixGroupIndexAtTest extends PublicTestCase
{
    use CreatesSeason;

    /**
     * @throws MockException
     * @throws Exception
     */
    #[Test]
    public function getAffixGroupIndexAt_GivenNoActiveSeason_ShouldReturnNull(): void
    {
        // Arrange
        $region = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();
        $date   = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getSeasonAt')->willReturn(null);

        $service = new SeasonAffixGroupService(
            $seasonService,
            $this->createMock(TimewalkingEventServiceInterface::class),
        );

        // Act
        $result = $service->getAffixGroupIndexAt($date, $region);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws MockException
     * @throws Exception
     */
    #[Test]
    public function getAffixGroupIndexAt_GivenDateAtSeasonStart_ShouldReturnStartAffixGroupIndex(): void
    {
        // Arrange - season starts Monday 2020-01-20
        // US: startOfWeek=2020-01-20 + 1 day + 15h = 2020-01-21 15:00 UTC
        $region = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();

        $season = new Season([
            'start'                   => Carbon::create(2020, 1, 20, 0, 0, 0, 'UTC'),
            'start_affix_group_index' => 3,
            'affix_group_count'       => 12,
        ]);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getSeasonAt')->willReturn($season);

        $service = new SeasonAffixGroupService(
            $seasonService,
            $this->createMock(TimewalkingEventServiceInterface::class),
        );

        // Date is exactly at season start (2020-01-21 15:00 UTC = 0 weeks elapsed)
        $date = Carbon::create(2020, 1, 21, 16, 0, 0, 'UTC');

        // Act
        $result = $service->getAffixGroupIndexAt($date, $region);

        // Assert: (3 + 0) % 12 = 3
        $this->assertEquals(3, $result);
    }

    /**
     * @throws MockException
     * @throws Exception
     */
    #[Test]
    public function getAffixGroupIndexAt_GivenDate2WeeksAfterSeasonStart_ShouldReturnCorrectIndex(): void
    {
        // Arrange - season starts Monday 2020-01-20
        // US: startOfWeek=2020-01-20 + 1 day + 15h = 2020-01-21 15:00 UTC
        $region = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();

        $season = new Season([
            'start'                   => Carbon::create(2020, 1, 20, 0, 0, 0, 'UTC'),
            'start_affix_group_index' => 0,
            'affix_group_count'       => 12,
        ]);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getSeasonAt')->willReturn($season);

        $service = new SeasonAffixGroupService(
            $seasonService,
            $this->createMock(TimewalkingEventServiceInterface::class),
        );

        // Date is 2 weeks after season start
        $date = Carbon::create(2020, 2, 4, 16, 0, 0, 'UTC');

        // Act
        $result = $service->getAffixGroupIndexAt($date, $region);

        // Assert: (0 + 2) % 12 = 2
        $this->assertEquals(2, $result);
    }

    /**
     * @throws MockException
     * @throws Exception
     */
    #[Test]
    public function getAffixGroupIndexAt_GivenDateAfterFullCycle_ShouldWrapAround(): void
    {
        // Arrange - season starts Monday 2020-01-20, 12 affix groups = one full cycle per 12 weeks
        // US: 2020-01-21 15:00 UTC
        $region = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();

        $season = new Season([
            'start'                   => Carbon::create(2020, 1, 20, 0, 0, 0, 'UTC'),
            'start_affix_group_index' => 0,
            'affix_group_count'       => 12,
        ]);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getSeasonAt')->willReturn($season);

        $service = new SeasonAffixGroupService(
            $seasonService,
            $this->createMock(TimewalkingEventServiceInterface::class),
        );

        // Date is 13 weeks after season start: (0 + 13) % 12 = 1
        $date = Carbon::create(2020, 4, 21, 16, 0, 0, 'UTC');

        // Act
        $result = $service->getAffixGroupIndexAt($date, $region);

        // Assert: (0 + 13) % 12 = 1
        $this->assertEquals(1, $result);
    }

    /**
     * @throws MockException
     */
    #[Test]
    public function getAffixGroupIndexAt_GivenSeasonStartAfterDate_ShouldThrowException(): void
    {
        // Arrange - season start with offsets results in a date after our test date
        // Season starts 2020-02-01 (Saturday). US offsets: startOfWeek=2020-01-27 + 1 day + 15h = 2020-01-28 15:00 UTC
        $region = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();

        $season = new Season([
            'start'                   => Carbon::create(2020, 2, 1, 0, 0, 0, 'UTC'),
            'start_affix_group_index' => 0,
            'affix_group_count'       => 12,
        ]);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        // Mock returns this season even though the test date is before the season starts
        $seasonService->method('getSeasonAt')->willReturn($season);

        $service = new SeasonAffixGroupService(
            $seasonService,
            $this->createMock(TimewalkingEventServiceInterface::class),
        );

        // Date is before the season's regional start (2020-01-28 15:00 UTC)
        $date = Carbon::create(2020, 1, 1, 0, 0, 0, 'UTC');

        // Act & Assert
        $this->expectException(Exception::class);
        $service->getAffixGroupIndexAt($date, $region);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getAffixGroupIndexAt_givenFirstResetOfMidWeekStartingSeason_returnsStartAffixGroupIndex(): void
    {
        // Arrange
        $service      = app(SeasonAffixGroupServiceInterface::class);
        $usRegion     = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();
        $twwExpansion = Expansion::where('shortname', Expansion::EXPANSION_TWW)->firstOrFail();
        // Wednesday 2030-01-09: its first reset is counted from Monday 2030-01-07
        $season = $this->createSeason([
            'expansion_id'            => $twwExpansion->id,
            'start'                   => '2030-01-09 00:00:00',
            'start_affix_group_index' => 5,
            'affix_group_count'       => 8,
        ]);
        $date = Carbon::create(2030, 1, 7, 0, 0, 0, 'UTC')
            ->addDays($usRegion->reset_day_offset)
            ->addHours($usRegion->reset_hours_offset);

        // Act
        $result = $service->getAffixGroupIndexAt($date, $usRegion, $season->expansion);

        // Assert
        $this->assertSame(5, $result);
    }
}
