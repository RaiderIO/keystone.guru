<?php

namespace Tests\Feature\App\Service\Creator\CreatorDirectoryService;

use App\Models\GameVersion\GameVersion;
use App\Service\Creator\CreatorDirectoryServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CreatorDirectoryService')]
#[Group('GetStatsSeason')]
final class GetStatsSeasonTest extends PublicTestCase
{
    #[Test]
    public function getStatsSeason_givenGameVersionWithSeasons_returnsTheCurrentSeason(): void
    {
        // Arrange
        $retail = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $this->assertTrue((bool)$retail->has_seasons, 'Fixture assumption changed - retail must have seasons.');
        $currentSeason = app(SeasonServiceInterface::class)->getCurrentSeason();
        $this->assertNotNull($currentSeason, 'Expected a current season to be seeded.');

        // Act
        $result = app(CreatorDirectoryServiceInterface::class)->getStatsSeason($retail);

        // Assert
        $this->assertSame($currentSeason->id, $result?->id);
    }

    #[Test]
    public function getStatsSeason_givenGameVersionWithoutSeasons_returnsNull(): void
    {
        // Arrange
        $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $this->assertFalse((bool)$classicEra->has_seasons, 'Fixture assumption changed - classic era must have no seasons.');
        $this->assertTrue((bool)GameVersion::getDefaultGameVersion()->has_seasons, 'Fixture assumption changed - the default game version must have seasons.');

        // Act
        $result = app(CreatorDirectoryServiceInterface::class)->getStatsSeason($classicEra);

        // Assert
        $this->assertNull($result);
    }
}
