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
#[Group('GetMostRecentSeasonForDungeon')]
final class GetMostRecentSeasonForDungeonTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getMostRecentSeasonForDungeon_GivenDungeonWithNoMappingVersionWithSeasons_ShouldReturnNull(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        /** @var MockObject&Dungeon $dungeon */
        $dungeon = $this->createPartialMockPublic(Dungeon::class, ['hasMappingVersionWithSeasons']);
        $dungeon->method('hasMappingVersionWithSeasons')->willReturn(false);

        // Act
        $result = $service->getMostRecentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getMostRecentSeasonForDungeon_GivenDungeonWithMappingVersionWithSeasons_ShouldDelegateToRepository(): void
    {
        // Arrange
        $expectedSeason = Season::findOrFail(Season::SEASON_BFA_S4);

        /** @var MockObject&Dungeon $dungeon */
        $dungeon = $this->createPartialMockPublic(Dungeon::class, ['hasMappingVersionWithSeasons']);
        $dungeon->method('hasMappingVersionWithSeasons')->willReturn(true);

        // Use full mock of repository (no onlyMethods — avoids unimplemented abstract methods from BaseRepositoryInterface)
        $seasonRepository = RepositoryFixtures::getSeasonRepositoryMock($this);
        $seasonRepository->expects($this->once())
            ->method('getMostRecentSeasonForDungeon')
            ->with($dungeon)
            ->willReturn($expectedSeason);

        /** @var MockObject&SeasonService $service */
        $service = $this->getMockBuilderPublic(SeasonService::class)
            ->setConstructorArgs([
                ServiceFixtures::getExpansionServiceMock($this),
                $seasonRepository,
            ])
            ->onlyMethods([])
            ->getMock();

        // Act
        $result = $service->getMostRecentSeasonForDungeon($dungeon);

        // Assert
        $this->assertEquals($expectedSeason->id, $result->id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getMostRecentSeasonForDungeon_GivenDungeonWithMappingVersionWithSeasons_WhenRepositoryReturnsNull_ShouldReturnNull(): void
    {
        // Arrange
        /** @var MockObject&Dungeon $dungeon */
        $dungeon = $this->createPartialMockPublic(Dungeon::class, ['hasMappingVersionWithSeasons']);
        $dungeon->method('hasMappingVersionWithSeasons')->willReturn(true);

        $seasonRepository = RepositoryFixtures::getSeasonRepositoryMock($this);
        $seasonRepository->method('getMostRecentSeasonForDungeon')->willReturn(null);

        /** @var MockObject&SeasonService $service */
        $service = $this->getMockBuilderPublic(SeasonService::class)
            ->setConstructorArgs([
                ServiceFixtures::getExpansionServiceMock($this),
                $seasonRepository,
            ])
            ->onlyMethods([])
            ->getMock();

        // Act
        $result = $service->getMostRecentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }
}
