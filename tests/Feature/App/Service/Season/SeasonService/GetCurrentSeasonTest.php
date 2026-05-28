<?php

namespace App\Service\Season\SeasonService;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetCurrentSeason')]
final class GetCurrentSeasonTest extends PublicTestCase
{
    #[Test]
    public function getCurrentSeason_GivenMidnightExpansion_ShouldReturnMidnightS1(): void
    {
        // Arrange - Midnight S1 started 2026-03-02, today is 2026-05-28
        $this->travelTo(Carbon::create(2026, 05, 28));

        $service           = app(SeasonServiceInterface::class);
        $midnightExpansion = Expansion::where('shortname', Expansion::EXPANSION_MIDNIGHT)->firstOrFail();
        $usRegion          = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = $service->getCurrentSeason($midnightExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_MIDNIGHT_S1, $result->id);
    }

    #[Test]
    public function getCurrentSeason_GivenBfaExpansion_ShouldReturnBfaS4(): void
    {
        // Arrange - BFA S4 is the last BFA season (2020-01-21), so it's the "current" BFA season
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = $service->getCurrentSeason($bfaExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_BFA_S4, $result->id);
    }

    #[Test]
    public function getCurrentSeason_GivenBfaExpansion_ShouldReturnSeasonForSameExpansion(): void
    {
        // Arrange
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = $service->getCurrentSeason($bfaExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($bfaExpansion->id, $result->expansion_id);
    }

    #[Test]
    public function getCurrentSeason_GivenOneMinuteBeforeSeasonEnd_ShouldReturnOldSeason(): void
    {
        // Arrange - TWW S3 starts 2025-03-03
        $service      = app(SeasonServiceInterface::class);
        $twwExpansion = Expansion::where('shortname', Expansion::EXPANSION_TWW)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $this->travelTo(
            Carbon::create(2025, 03, 03)
                ->addDays($usRegion->reset_day_offset)->addHours($usRegion->reset_hours_offset)->subMinute(),
        );

        // Act
        $result = $service->getCurrentSeason($twwExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_TWW_S1, $result->id);
    }

    #[Test]
    public function getCurrentSeason_GivenOneMinuteIntoNewSeason_ShouldReturnNewSeason(): void
    {
        // Arrange - TWW S3 starts 2025-03-03
        $service      = app(SeasonServiceInterface::class);
        $twwExpansion = Expansion::where('shortname', Expansion::EXPANSION_TWW)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $this->travelTo(
            Carbon::create(2025, 03, 03)
                ->addDays($usRegion->reset_day_offset)->addHours($usRegion->reset_hours_offset)->addMinute(),
        );

        // Act
        $result = $service->getCurrentSeason($twwExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_TWW_S2, $result->id);
    }

    #[Test]
    public function getCurrentSeason_GivenOneMinuteBeforeExpansionEnd_ShouldReturnOldSeason(): void
    {
        // Arrange - Midnight S1 starts 2026-03-02
        $service      = app(SeasonServiceInterface::class);
        $twwExpansion = Expansion::where('shortname', Expansion::EXPANSION_TWW)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $this->travelTo(
            Carbon::create(2026, 03, 02)
                ->addDays($usRegion->reset_day_offset)->addHours($usRegion->reset_hours_offset)->subMinute(),
        );

        // Act
        $result = $service->getCurrentSeason($twwExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_TWW_S3, $result->id);
    }

    #[Test]
    public function getCurrentSeason_GivenOneMinuteIntoNewExpansion_ShouldReturnNewSeason(): void
    {
        // Arrange - Midnight S1 starts 2026-03-02
        $service           = app(SeasonServiceInterface::class);
        $midnightExpansion = Expansion::where('shortname', Expansion::EXPANSION_MIDNIGHT)->firstOrFail();
        $usRegion          = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $this->travelTo(
            Carbon::create(2026, 03, 02)
                ->addDays($usRegion->reset_day_offset)->addHours($usRegion->reset_hours_offset)->addMinute(),
        );

        // Act
        $result = $service->getCurrentSeason($midnightExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_MIDNIGHT_S1, $result->id);
    }
}
