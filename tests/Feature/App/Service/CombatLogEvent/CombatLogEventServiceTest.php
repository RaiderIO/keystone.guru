<?php

namespace Tests\Feature\App\Service\CombatLogEvent;

use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\CombatLogEvent\CombatLogEventService;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLogEventService')]
final class CombatLogEventServiceTest extends PublicTestCase
{
    /**
     * Season::start_period was removed (#4472) because it derived a leaderboard period from the raw
     * `start` column instead of the region-normalised, reset-aligned start SeasonService::getSeasonWeeks()
     * uses - this pins the fake-run generator's replacement onto that authoritative source, for every
     * seeded season in both regions the accessor used to silently disagree with.
     */
    #[Test]
    #[TestWith([GameServerRegion::AMERICAS])]
    #[TestWith([GameServerRegion::EUROPE])]
    public function resolveSeasonStartPeriod_givenEverySeededSeason_matchesFirstSeasonWeekPeriod(string $regionShort): void
    {
        // Arrange
        /** @var CombatLogEventService $service */
        $service       = app(CombatLogEventServiceInterface::class);
        $seasonService = app(SeasonServiceInterface::class);
        $region        = GameServerRegion::where('short', $regionShort)->firstOrFail();
        $method        = new ReflectionMethod($service, 'resolveSeasonStartPeriod');

        foreach ($seasonService->getAllSeasons() as $season) {
            /** @var Season $season */
            // Act
            $result = $method->invoke($service, $season, $region);

            // Assert
            $seasonWeeks = $seasonService->getSeasonWeeks($season, $region);

            if ($seasonWeeks->isEmpty()) {
                // Season has not started yet - no week to compare against, fall back to a direct calculation
                $this->assertSame($region->getKeystoneLeaderboardPeriod($season->start($region)->addDays(3)), $result);
            } else {
                $this->assertSame($seasonWeeks->first()->period, $result);
            }
        }
    }
}
