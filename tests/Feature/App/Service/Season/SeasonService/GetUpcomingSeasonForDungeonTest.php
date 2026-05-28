<?php

namespace App\Service\Season\SeasonService;

use App\Models\Dungeon;
use App\Models\Season;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\RepositoryFixtures;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetUpcomingSeasonForDungeon')]
final class GetUpcomingSeasonForDungeonTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getUpcomingSeasonForDungeon_GivenDungeonWithNoMappingVersionWithSeasons_ShouldReturnNull(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        /** @var Dungeon|MockObject $dungeon */
        $dungeon = $this->createPartialMockPublic(Dungeon::class, ['hasMappingVersionWithSeasons']);
        $dungeon->method('hasMappingVersionWithSeasons')->willReturn(false);

        // Act
        $result = $service->getUpcomingSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getUpcomingSeasonForDungeon_GivenDungeonWithMappingVersionWithSeasons_ShouldDelegateToRepository(): void
    {
        // Arrange
        $expectedSeason = Season::findOrFail(Season::SEASON_BFA_S4);

        /** @var Dungeon|MockObject $dungeon */
        $dungeon = $this->createPartialMockPublic(Dungeon::class, ['hasMappingVersionWithSeasons']);
        $dungeon->method('hasMappingVersionWithSeasons')->willReturn(true);

        // Use full mock (no onlyMethods — avoids unimplemented abstract methods from BaseRepositoryInterface)
        $seasonRepository = RepositoryFixtures::getSeasonRepositoryMock($this);
        $seasonRepository->expects($this->once())
            ->method('getUpcomingSeasonForDungeon')
            ->with($dungeon)
            ->willReturn($expectedSeason);

        /** @var SeasonService|MockObject $service */
        $service = $this->getMockBuilderPublic(SeasonService::class)
            ->setConstructorArgs([
                ServiceFixtures::getExpansionServiceMock($this),
                $seasonRepository,
            ])
            ->onlyMethods([])
            ->getMock();

        // Act
        $result = $service->getUpcomingSeasonForDungeon($dungeon);

        // Assert
        $this->assertEquals($expectedSeason->id, $result->id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getUpcomingSeasonForDungeon_GivenDungeonWithMappingVersionWithSeasons_WhenRepositoryReturnsNull_ShouldReturnNull(): void
    {
        // Arrange
        /** @var Dungeon|MockObject $dungeon */
        $dungeon = $this->createPartialMockPublic(Dungeon::class, ['hasMappingVersionWithSeasons']);
        $dungeon->method('hasMappingVersionWithSeasons')->willReturn(true);

        $seasonRepository = RepositoryFixtures::getSeasonRepositoryMock($this);
        $seasonRepository->method('getUpcomingSeasonForDungeon')->willReturn(null);

        /** @var SeasonService|MockObject $service */
        $service = $this->getMockBuilderPublic(SeasonService::class)
            ->setConstructorArgs([
                ServiceFixtures::getExpansionServiceMock($this),
                $seasonRepository,
            ])
            ->onlyMethods([])
            ->getMock();

        // Act
        $result = $service->getUpcomingSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }
}
