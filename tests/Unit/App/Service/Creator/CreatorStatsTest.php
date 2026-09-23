<?php

namespace Tests\Unit\App\Service\Creator;

use App\Models\Season;
use App\Service\Creator\Dtos\CreatorStats;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CreatorStats')]
final class CreatorStatsTest extends PublicTestCase
{
    #[Test]
    public function getSummaryParts_givenRoutesThisSeason_returnsTheSeasonCountAndItsViews(): void
    {
        // Arrange
        $creatorStats = CreatorStats::fromAttributes([
            'published_route_count' => 140,
            'total_views'           => 99000,
            'season_route_count'    => 12,
            'season_views'          => 8400,
        ], $this->season());

        // Act
        $parts = $creatorStats->getSummaryParts();

        // Assert
        $this->assertSame(['12 routes this season', '8.4K views'], $parts);
    }

    #[Test]
    public function getSummaryParts_givenNoRoutesThisSeason_returnsTheTotalInstead(): void
    {
        // Arrange
        $creatorStats = CreatorStats::fromAttributes([
            'published_route_count' => 140,
            'total_views'           => 99000,
            'season_route_count'    => 0,
            'season_views'          => 0,
        ], $this->season());

        // Act
        $parts = $creatorStats->getSummaryParts();

        // Assert
        $this->assertSame(['No routes this season', '140 routes total'], $parts);
    }

    #[Test]
    public function getSummaryParts_givenNoSeason_returnsAllTimeRoutesAndViews(): void
    {
        // Arrange - season figures present in the row must not leak through without a season
        $creatorStats = CreatorStats::fromAttributes([
            'published_route_count' => 1,
            'total_views'           => 1,
            'season_route_count'    => 5,
            'season_views'          => 500,
        ], null);

        // Act
        $parts = $creatorStats->getSummaryParts();

        // Assert
        $this->assertSame(['1 route', '1 view'], $parts);
        $this->assertSame(0, $creatorStats->seasonRouteCount);
    }

    #[Test]
    public function getProfileParts_givenEnoughRatings_includesTheWeightedAverage(): void
    {
        // Arrange
        Carbon::setTestNow('2026-09-23 12:00:00');
        config(['keystoneguru.creators.min_ratings_shown' => 5]);
        $season       = $this->season();
        $creatorStats = CreatorStats::fromAttributes([
            'published_route_count' => 140,
            'total_views'           => 99000,
            'season_route_count'    => 12,
            'season_views'          => 8400,
            'rating_weighted_sum'   => 23,
            'rating_count'          => 5,
            'last_published_at'     => '2026-09-20 12:00:00',
        ], $season);

        try {
            // Act
            $parts = $creatorStats->getProfileParts();

            // Assert
            $this->assertSame([
                sprintf('12 routes in %s', $season->name_long),
                '8.4K views',
                '★ 4.6 (5 ratings)',
                '140 routes total',
                'Last published 3 days ago',
            ], $parts);
        } finally {
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function getProfileParts_givenFewerRatingsThanTheMinimum_leavesTheRatingOut(): void
    {
        // Arrange
        config(['keystoneguru.creators.min_ratings_shown' => 5]);
        $creatorStats = CreatorStats::fromAttributes([
            'published_route_count' => 3,
            'total_views'           => 10,
            'rating_weighted_sum'   => 20,
            'rating_count'          => 4,
        ], null);

        // Act
        $parts = $creatorStats->getProfileParts();

        // Assert
        $this->assertFalse($creatorStats->hasEnoughRatingsToShow());
        $this->assertSame(['3 routes total', '10 views'], $parts);
    }

    #[Test]
    public function getProfileParts_givenNoPublishedRoutes_returnsOnlyThat(): void
    {
        // Arrange
        $creatorStats = CreatorStats::fromAttributes([], $this->season());

        // Act
        $parts = $creatorStats->getProfileParts();

        // Assert
        $this->assertSame(['No published routes'], $parts);
    }

    private function season(): Season
    {
        /** @var Season|null $season */
        $season = Season::query()->with('expansion')->first();
        $this->assertNotNull($season, 'Expected a seeded season');

        return $season;
    }
}
