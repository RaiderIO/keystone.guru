<?php

namespace Tests\Feature\App\Service\Season\SeasonService;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Season;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Service\Season\SeasonService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\RepositoryFixtures;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetActiveSeasonForDungeon')]
final class GetActiveSeasonForDungeonTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getActiveSeasonForDungeon_givenUpcomingAndMostRecentSeason_returnsUpcomingSeason(): void
    {
        // Arrange
        $upcomingSeason   = Season::findOrFail(Season::SEASON_SL_S4);
        $mostRecentSeason = Season::findOrFail(Season::SEASON_BFA_S4);

        $seasonRepository = RepositoryFixtures::getSeasonRepositoryMock($this);
        $seasonRepository->method('getUpcomingSeasonForDungeon')->willReturn($upcomingSeason);
        $seasonRepository->method('getMostRecentSeasonForDungeon')->willReturn($mostRecentSeason);

        $service = $this->createSeasonService($seasonRepository);

        // Act
        $result = $service->getActiveSeasonForDungeon($this->createDungeon());

        // Assert
        $this->assertSame($upcomingSeason->id, $result?->id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getActiveSeasonForDungeon_givenNoUpcomingSeason_returnsMostRecentSeason(): void
    {
        // Arrange
        $mostRecentSeason = Season::findOrFail(Season::SEASON_BFA_S4);

        $seasonRepository = RepositoryFixtures::getSeasonRepositoryMock($this);
        $seasonRepository->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonRepository->method('getMostRecentSeasonForDungeon')->willReturn($mostRecentSeason);

        $service = $this->createSeasonService($seasonRepository);

        // Act
        $result = $service->getActiveSeasonForDungeon($this->createDungeon());

        // Assert
        $this->assertSame($mostRecentSeason->id, $result?->id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getActiveSeasonForDungeon_givenNoDungeonSeasons_returnsCurrentSeasonOfTheDungeonsExpansion(): void
    {
        // Arrange
        $expansion     = new Expansion()->forceFill(['id' => 1234]);
        $currentSeason = Season::findOrFail(Season::SEASON_SL_S4);

        $seasonRepository = RepositoryFixtures::getSeasonRepositoryMock($this);
        $seasonRepository->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonRepository->method('getMostRecentSeasonForDungeon')->willReturn(null);

        $service = $this->createSeasonService($seasonRepository, ['getCurrentSeason']);
        $service->expects($this->once())
            ->method('getCurrentSeason')
            ->with($expansion)
            ->willReturn($currentSeason);

        // Act
        $result = $service->getActiveSeasonForDungeon($this->createDungeon()->setRelation('expansion', $expansion));

        // Assert
        $this->assertSame($currentSeason->id, $result?->id);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getActiveSeasonForDungeon_givenSameDungeonTwice_resolvesTheSeasonOnce(): void
    {
        // Arrange
        $upcomingSeason = Season::findOrFail(Season::SEASON_SL_S4);

        $seasonRepository = RepositoryFixtures::getSeasonRepositoryMock($this);
        $seasonRepository->expects($this->once())
            ->method('getUpcomingSeasonForDungeon')
            ->willReturn($upcomingSeason);

        $service = $this->createSeasonService($seasonRepository);

        // Act
        $firstResult  = $service->getActiveSeasonForDungeon($this->createDungeon());
        $secondResult = $service->getActiveSeasonForDungeon($this->createDungeon());

        // Assert
        $this->assertSame($upcomingSeason->id, $firstResult?->id);
        $this->assertSame($firstResult, $secondResult);
    }

    /**
     * @return MockObject&Dungeon
     *
     * @throws Exception
     */
    private function createDungeon(): Dungeon
    {
        /** @var MockObject&Dungeon $dungeon */
        $dungeon = $this->createPartialMockPublic(Dungeon::class, ['hasMappingVersionWithSeasons']);
        $dungeon->method('hasMappingVersionWithSeasons')->willReturn(true);
        $dungeon->id = 4321;

        return $dungeon;
    }

    /**
     * @param array<int, string> $methodsToMock
     *
     * @return MockObject&SeasonService
     */
    private function createSeasonService(
        SeasonRepositoryInterface $seasonRepository,
        array                     $methodsToMock = [],
    ): SeasonService {
        /** @var MockObject&SeasonService $service */
        $service = $this->getMockBuilderPublic(SeasonService::class)
            ->setConstructorArgs([
                ServiceFixtures::getExpansionServiceMock($this),
                $seasonRepository,
            ])
            ->onlyMethods($methodsToMock)
            ->getMock();

        return $service;
    }
}
