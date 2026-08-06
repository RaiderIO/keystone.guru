<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\Expansion;
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
        // Arrange - a dungeon with no upcoming season of its own, so it unambiguously picks up the one
        // this test attaches; only the season and its season_dungeons row are test data
        $now = now();

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')
            ->whereDoesntHave('seasonDungeons.season', static function ($query) use ($now) {
                $query->where('start', '>', $now);
            })
            ->firstOrFail();
        $upcomingSeason = null;

        try {
            $upcomingSeason = Season::create([
                'expansion_id'            => Expansion::firstWhere('shortname', Expansion::EXPANSION_MIDNIGHT)->id,
                'seasonal_affix_id'       => null,
                'index'                   => 99,
                'start'                   => now()->addYears(2)->toDateTimeString(),
                'active'                  => false,
                'presets'                 => 0,
                'affix_group_count'       => 8,
                'start_affix_group_index' => 0,
                'key_level_min'           => 2,
                'key_level_max'           => 25,
                'item_level_min'          => 240,
                'item_level_max'          => 300,
            ]);
            $upcomingSeason->syncDungeons([$dungeon->id]);

            // Act
            $result = $this->repository->getUpcomingSeasonForDungeon($dungeon);

            // Assert
            $this->assertNotNull($result);
            $this->assertSame($upcomingSeason->id, $result->id);
        } finally {
            // Individually deleted (not the dungeon itself, which is real seeded data) so each
            // SeasonDungeon's cache is properly invalidated, matching Season::syncDungeons().
            $upcomingSeason?->seasonDungeons()->get()->each->delete();
            $upcomingSeason?->delete();
        }
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
