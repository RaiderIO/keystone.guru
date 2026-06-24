<?php

namespace App\Service\Season\SeasonService;

use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetSeasonFromShortString')]
final class GetSeasonFromShortStringTest extends PublicTestCase
{
    #[Test]
    public function getSeasonFromShortString_GivenEmptyString_ShouldReturnNull(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getSeasonFromShortString('');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getSeasonFromShortString_GivenMalformedString_ShouldReturnNull(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getSeasonFromShortString('invalid-format');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getSeasonFromShortString_GivenUnknownExpansionShortName_ShouldReturnNull(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getSeasonFromShortString('s1-xyz-1');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getSeasonFromShortString_GivenValidBfaS1String_ShouldReturnBfaS1(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act - format is "s{something}-{expansion_shortname}-{season_index}"
        $result = $service->getSeasonFromShortString('s1-bfa-1');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_BFA_S1, $result->id);
    }

    #[Test]
    public function getSeasonFromShortString_GivenValidMidnightS1String_ShouldReturnMidnightS1(): void
    {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getSeasonFromShortString('s17-midnight-1');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_MIDNIGHT_S1, $result->id);
    }

    /**
     * @throws \Exception
     */
    #[Test]
    #[DataProvider('getSeasonFromShortString_GivenValidStrings_ShouldReturnCorrectSeason_dataProvider')]
    public function getSeasonFromShortString_GivenValidString_ShouldReturnCorrectSeason(
        string $shortString,
        int    $expectedSeasonId,
    ): void {
        // Arrange
        $service = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getSeasonFromShortString($shortString);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($expectedSeasonId, $result->id);
    }

    /**
     * @return array<string, list<int|string>>
     */
    public static function getSeasonFromShortString_GivenValidStrings_ShouldReturnCorrectSeason_dataProvider(): array
    {
        return [
            'BFA S1'      => ['s1-bfa-1', Season::SEASON_BFA_S1],
            'BFA S2'      => ['s2-bfa-2', Season::SEASON_BFA_S2],
            'BFA S4'      => ['s4-bfa-4', Season::SEASON_BFA_S4],
            'TWW S1'      => ['s14-tww-1', Season::SEASON_TWW_S1],
            'Midnight S1' => ['s17-midnight-1', Season::SEASON_MIDNIGHT_S1],
        ];
    }
}
