<?php

namespace App\Service\Season\SeasonService;

use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetNextSeason')]
final class GetNextSeasonTest extends PublicTestCase
{
    #[Test]
    public function getNextSeason_GivenBfaS1_ShouldReturnBfaS2(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);
        $bfaS1   = Season::findOrFail(Season::SEASON_BFA_S1);

        // Act
        $result = $service->getNextSeason($bfaS1);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_BFA_S2, $result->id);
        $this->assertTrue($result->start->isAfter($bfaS1->start));
    }

    #[Test]
    public function getNextSeason_GivenBfaS2_ShouldReturnBfaS3(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);
        $bfaS2   = Season::findOrFail(Season::SEASON_BFA_S2);

        // Act
        $result = $service->getNextSeason($bfaS2);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_BFA_S3, $result->id);
    }

    #[Test]
    public function getNextSeason_GivenBfaS4_ShouldReturnNull(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);
        $bfaS4   = Season::findOrFail(Season::SEASON_BFA_S4);

        // Act
        $result = $service->getNextSeason($bfaS4);

        // Assert - BFA S4 is the last BFA season, there is no next season within BFA
        $this->assertNull($result);
    }
}
