<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Season;
use App\Repositories\Database\SeasonRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonRepository')]
final class SeasonRepositoryTest extends PublicTestCase
{
    use CreatesDungeon;
    use CreatesSeason;

    private SeasonRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new SeasonRepository();
    }

    #[Test]
    public function getMostRecentSeasonForDungeon_givenDungeonWithPastSeasons_returnsSeason(): void
    {
        // Arrange — find a dungeon that participates in at least one past season
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereHas('seasonDungeons.season', static function ($query) {
            $query->where('start', '<=', now());
        })->first();

        // Act
        $result = $this->repository->getMostRecentSeasonForDungeon($dungeon);

        // Assert
        $this->assertInstanceOf(Season::class, $result);
        $this->assertLessThanOrEqual(now(), $result->start);
    }

    #[Test]
    public function getMostRecentSeasonForDungeon_givenDungeonWithNoSeasons_returnsNull(): void
    {
        // Arrange — a dungeon of our own, which no season is attached to
        $dungeon = $this->createDungeon();

        // Act
        $result = $this->repository->getMostRecentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getUpcomingSeasonForDungeon_givenDungeonWithNoUpcomingSeasons_returnsNull(): void
    {
        // Arrange — a dungeon of our own whose only season has already started
        $dungeon = $this->createDungeon();
        $this->createSeason(['start' => now()->subYear()->toDateTimeString()], [$dungeon->id]);

        // Act
        $result = $this->repository->getUpcomingSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }

    /**
     * A `season_dungeons` row is a deliberate, curated assignment - not speculative noise - so it must be
     * honored regardless of how far out its season's start date is, rather than falling back to a stale
     * historical season (#3868).
     */
    #[Test]
    public function getUpcomingSeasonForDungeon_givenDungeonWithSeasonMoreThanAYearOut_returnsThatSeason(): void
    {
        // Arrange - a dungeon of our own, so the season attached here is unambiguously its only upcoming one
        $dungeon        = $this->createDungeon();
        $upcomingSeason = $this->createSeason([
            'expansion_id' => Expansion::firstWhere('shortname', Expansion::EXPANSION_MIDNIGHT)->id,
            'start'        => now()->addYears(2)->toDateTimeString(),
        ], [$dungeon->id]);

        // Act
        $result = $this->repository->getUpcomingSeasonForDungeon($dungeon);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($upcomingSeason->id, $result->id);
    }

    /**
     * Season::SEASON_LEGION_TW_S1 is seeded with a deliberately far-future placeholder start date
     * (2050) precisely so it is never treated as a dungeon's upcoming season - dropping the cap
     * outright would resurrect its stale seasonal affix for every Legion dungeon it's attached to
     * (#3868 cold review).
     */
    #[Test]
    public function getUpcomingSeasonForDungeon_givenDungeonWithOnlyTheFarFuturePlaceholderSeason_returnsNull(): void
    {
        // Arrange
        $placeholderSeason = Season::findOrFail(Season::SEASON_LEGION_TW_S1);
        $this->assertGreaterThan(now()->addYears(3), $placeholderSeason->start, 'Fixture assumption changed - the placeholder season must still sit well past the lookahead cap.');

        /** @var Dungeon $dungeon */
        $dungeon = $placeholderSeason->dungeons()->firstOrFail();

        // Act
        $result = $this->repository->getUpcomingSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getMostRecentSeasonForDungeon_givenDungeonWithMultipleSeasons_returnsMostRecent(): void
    {
        // Arrange — a dungeon of our own with two past seasons, so the ordering is what is being confirmed
        $dungeon = $this->createDungeon();
        $this->createSeason(['start' => now()->subYears(2)->toDateTimeString()], [$dungeon->id]);
        $expectedSeason = $this->createSeason(['start' => now()->subYear()->toDateTimeString()], [$dungeon->id]);

        // Act
        $result = $this->repository->getMostRecentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($expectedSeason->id, $result->id);
    }
}
