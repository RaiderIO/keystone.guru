<?php

namespace App\Service\Season\SeasonService;

use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetFirstSeason')]
final class GetFirstSeasonTest extends PublicTestCase
{
    #[Test]
    public function getFirstSeason_ShouldReturnBfaS1AsEarliestSeason(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getFirstSeason();

        // Assert - BFA S1 is the first season (2018-09-04), const id = 1
        $this->assertInstanceOf(Season::class, $result);
        $this->assertEquals(Season::SEASON_BFA_S1, $result->id);
    }

    #[Test]
    public function getFirstSeason_CalledTwice_ShouldReturnSameSeason(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        $result1 = $service->getFirstSeason();
        $result2 = $service->getFirstSeason();

        // Assert
        $this->assertEquals($result1->id, $result2->id);
    }
}
