<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Season;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the invariants of the season data that SeasonsSeeder imports from database/seeders/seasondata/.
 *
 * @see AffixSeederTest for why these matter more now that the data is admin-authored and exported.
 */
#[Group('SeasonsSeeder')]
final class SeasonsSeederTest extends PublicTestCase
{
    #[Test]
    public function run_givenSeededDatabase_mirrorsTheAllSeasonsConstant(): void
    {
        // Arrange
        $expected = Season::ALL_SEASONS;

        // Act
        $actual = Season::query()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        // Assert - the admin panel can create a season, but it cannot add the SEASON_* constant that
        // the rest of the application resolves seasons by, so this has to fail loudly.
        $this->assertSame(
            $expected,
            $actual,
            'The seeded seasons no longer match Season::ALL_SEASONS. Adding a season requires adding its SEASON_* constant and ALL_SEASONS entry too.',
        );
    }

    #[Test]
    public function run_givenSeededDatabase_keepsStartAffixGroupIndexWithinTheRotation(): void
    {
        // Arrange & Act - SeasonAffixGroupService resolves the current week as
        // ($season->start_affix_group_index + $elapsedWeeks) % $season->affix_group_count, so an index
        // at or beyond the rotation length silently shifts every week the site displays.
        $seasons = Season::all();

        // Assert
        foreach ($seasons as $season) {
            $this->assertGreaterThanOrEqual(
                0,
                $season->start_affix_group_index,
                sprintf('Season %d has a negative start_affix_group_index.', $season->id),
            );
            $this->assertLessThan(
                $season->affix_group_count,
                $season->start_affix_group_index,
                sprintf(
                    'Season %d starts at affix group index %d but only has a %d week rotation.',
                    $season->id,
                    $season->start_affix_group_index,
                    $season->affix_group_count,
                ),
            );
        }

        $this->assertGreaterThan(0, $seasons->count());
    }

    #[Test]
    public function run_givenSeededDatabase_assignsEveryDungeonOfASeasonToThatSeason(): void
    {
        // Arrange & Act - season_dungeons is now seeded from exported dungeon ids rather than from a
        // `whereIn('dungeons.key', [...])` lookup, so a stale export would silently drop dungeons.
        $seasonsWithoutDungeons = Season::query()
            ->whereDoesntHave('dungeons')
            ->pluck('id')
            ->all();

        // Assert
        $this->assertSame([], $seasonsWithoutDungeons, 'Every season must have at least one dungeon assigned.');
    }
}
