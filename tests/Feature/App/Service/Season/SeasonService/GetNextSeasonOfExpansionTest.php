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
#[Group('GetNextSeasonOfExpansion')]
final class GetNextSeasonOfExpansionTest extends PublicTestCase
{
    #[Test]
    public function getNextSeasonOfExpansion_GivenBfaExpansion_ShouldReturnNull(): void
    {
        // Arrange - all BFA seasons started in the past, so there is no next season
        $service      = app(SeasonServiceInterface::class);
        $bfaExpansion = Expansion::where('shortname', Expansion::EXPANSION_BFA)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = $service->getNextSeasonOfExpansion($bfaExpansion, $usRegion);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getNextSeasonOfExpansion_GivenExpansionWithFutureSeason_ShouldReturnFutureSeason(): void
    {
        // Arrange - TWW S1 started at 2024-09-16, S2 at 2025-03-03
        $service      = app(SeasonServiceInterface::class);
        $twwExpansion = Expansion::where('shortname', Expansion::EXPANSION_TWW)->firstOrFail();
        $usRegion     = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $this->travelTo(
            Carbon::create(2025)
                ->addDays($usRegion->reset_day_offset)->addHours($usRegion->reset_hours_offset)->subMinute(),
        );

        // Act
        $result = $service->getNextSeasonOfExpansion($twwExpansion, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(Season::SEASON_TWW_S2, $result->id);
    }
}
