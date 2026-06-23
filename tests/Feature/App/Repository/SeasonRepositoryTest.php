<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\Season;
use App\Repositories\Database\SeasonRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonRepository')]
final class SeasonRepositoryTest extends PublicTestCase
{
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
        // Arrange — find a dungeon that has no season_dungeon associations at all
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::doesntHave('seasonDungeons')->first();

        if ($dungeon === null) {
            $this->markTestSkipped('No dungeon without season associations found in the seeded database.');
        }

        // Act
        $result = $this->repository->getMostRecentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getUpcomingSeasonForDungeon_givenDungeonWithNoUpcomingSeasons_returnsNull(): void
    {
        // Arrange — find a dungeon that has no upcoming season
        $now = now();

        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->whereDoesntHave('seasonDungeons.season', static function ($query) use ($now) {
                $query->where('start', '>', $now);
            })
            ->first();

        if ($dungeon === null) {
            $this->markTestSkipped('No dungeon without an upcoming season found in the seeded database.');
        }

        // Act
        $result = $this->repository->getUpcomingSeasonForDungeon($dungeon);

        // Assert — the seeded test data is not expected to contain seasons starting more than a year from now
        $this->assertNull($result);
    }

    #[Test]
    public function getMostRecentSeasonForDungeon_givenDungeonWithMultipleSeasons_returnsMostRecent(): void
    {
        // Arrange — find a dungeon with at least two past seasons so we can confirm ordering
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::whereHas('seasonDungeons', static function (\Illuminate\Database\Eloquent\Builder $query): void {
            $query->whereHas('season', static function (\Illuminate\Database\Eloquent\Builder $seasonQuery): void {
                $seasonQuery->where('start', '<=', now());
            });
        }, '>=', 2)->first();

        if ($dungeon === null) {
            $this->markTestSkipped('No dungeon with two or more past seasons found in the seeded database.');
        }

        $expectedSeason = Season::join('season_dungeons', 'seasons.id', '=', 'season_dungeons.season_id')
            ->where('season_dungeons.dungeon_id', $dungeon->id)
            ->where('seasons.start', '<=', now())
            ->orderByDesc('seasons.start')
            ->select('seasons.*')
            ->first();

        // Act
        $result = $this->repository->getMostRecentSeasonForDungeon($dungeon);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($expectedSeason->id, $result->id);
    }
}
