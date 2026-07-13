<?php

namespace App\Service\Season\SeasonService;

use App\Models\Dungeon;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetCurrentSeasonForDungeon')]
final class GetCurrentSeasonForDungeonTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getCurrentSeasonForDungeon_GivenDungeonWithNoMappingVersionWithSeasons_ShouldReturnNull(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        /** @var MockObject&Dungeon $dungeon */
        $dungeon = $this->createPartialMockPublic(Dungeon::class, ['hasMappingVersionWithSeasons']);
        $dungeon->method('hasMappingVersionWithSeasons')->willReturn(false);

        // Act
        $result = $service->getCurrentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getCurrentSeasonForDungeon_GivenDungeonInCurrentSeason_ShouldReturnCurrentSeason(): void
    {
        // Arrange - Shadowlands S4 is active on 2022-09-01 and contains Operation Mechagon: Junkyard
        $this->travelTo(Carbon::create(2022, 9, 1));

        $service = app(SeasonServiceInterface::class);
        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();

        // Act
        $result = $service->getCurrentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_SL_S4, $result->id);
    }

    #[Test]
    public function getCurrentSeasonForDungeon_GivenDungeonNotInCurrentSeason_ShouldReturnNull(): void
    {
        // Arrange - Shadowlands S4 is active on 2022-09-01 but does not contain Pit of Saron
        $this->travelTo(Carbon::create(2022, 9, 1));

        $service = app(SeasonServiceInterface::class);
        $dungeon = Dungeon::where('key', 'pitofsaron')->firstOrFail();

        // Act
        $result = $service->getCurrentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }
}
