<?php

namespace Tests\Feature\App\Service\Season\SeasonService;

use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetSeasonEnd')]
final class GetSeasonEndTest extends PublicTestCase
{
    use CreatesSeason;

    #[Test]
    public function getSeasonEnd_givenSeasonFollowedByOneInTheSameExpansion_returnsThatSeasonsStart(): void
    {
        // Arrange
        $service  = app(SeasonServiceInterface::class);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $season   = Season::findOrFail(Season::SEASON_TWW_S2);

        // Act
        $result = $service->getSeasonEnd($season, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->eq(Season::findOrFail(Season::SEASON_TWW_S3)->start($usRegion)));
    }

    #[Test]
    public function getSeasonEnd_givenLastSeasonOfAnExpansion_returnsTheNextExpansionsFirstSeasonStart(): void
    {
        // Arrange
        $service  = app(SeasonServiceInterface::class);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $season   = Season::findOrFail(Season::SEASON_TWW_S3);

        // Act
        $result = $service->getSeasonEnd($season, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->eq(Season::findOrFail(Season::SEASON_MIDNIGHT_S1)->start($usRegion)));
    }

    #[Test]
    public function getSeasonEnd_givenSeasonFollowedByOneThatHasNotStarted_returnsThatSeasonsStart(): void
    {
        // Arrange
        $latestSeason   = $this->getLatestSeasonWithoutTimewalking();
        $upcomingStart  = Carbon::now()->greaterThan($latestSeason->start) ? Carbon::now() : $latestSeason->start->copy();
        $upcomingSeason = $this->createSeason([
            'expansion_id' => $latestSeason->expansion_id,
            'start'        => $upcomingStart->addWeeks(4)->toDateTimeString(),
        ]);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $service  = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getSeasonEnd($latestSeason, $usRegion);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->greaterThan(Carbon::now()));
        $this->assertTrue($result->eq($upcomingSeason->start($usRegion)));
    }

    #[Test]
    public function getSeasonEnd_givenSeasonWithNoLaterSeason_returnsNull(): void
    {
        // Arrange
        $latestSeason = $this->getLatestSeasonWithoutTimewalking();
        $lastSeason   = $this->createSeason([
            'expansion_id' => $latestSeason->expansion_id,
            'start'        => $latestSeason->start->copy()->addWeeks(20)->toDateTimeString(),
        ]);
        $usRegion = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();
        $service  = app(SeasonServiceInterface::class);

        // Act
        $result = $service->getSeasonEnd($lastSeason, $usRegion);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Timewalking seasons run alongside the regular ones and never end one, so they are no successor to build on.
     */
    private function getLatestSeasonWithoutTimewalking(): Season
    {
        return Season::query()
            ->whereDoesntHave('expansion.timewalkingEvent')
            ->orderByDesc('start')
            ->firstOrFail();
    }
}
