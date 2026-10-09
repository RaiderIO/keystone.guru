<?php

namespace Tests\Feature\App\Service\Expansion;

use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Expansion\ExpansionService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('ExpansionService')]
final class ExpansionServiceCurrentSeasonTest extends PublicTestCase
{
    /**
     * The Americas reset (Tuesday 15:00) comes before the European one (Wednesday 07:00), so in between a new
     * season has started in one region and not yet in the other. Both regions get the same legacy short, so a
     * cache keyed by anything but the region key hands Europe the Americas' season.
     */
    #[Test]
    public function getCurrentSeason_givenRegionsSharingLegacyShortBetweenTheirResets_cachesPerRegionKey(): void
    {
        // Arrange
        /** @var Season $season */
        $season = Season::query()->where('index', '>', 1)->orderBy('id')->firstOrFail();
        /** @var Season $previousSeason */
        $previousSeason = Season::query()
            ->where('expansion_id', $season->expansion_id)
            ->where('start', '<', $season->getRawOriginal('start'))
            ->orderByDesc('start')
            ->firstOrFail();
        $expansion = Expansion::query()->findOrFail($season->expansion_id);

        $americas        = GameServerRegion::query()->findOrFail(GameServerRegion::ALL[GameServerRegion::AMERICAS]);
        $europe          = GameServerRegion::query()->findOrFail(GameServerRegion::ALL[GameServerRegion::EUROPE]);
        $americas->short = 'legacy';
        $europe->short   = 'legacy';

        Carbon::setTestNow(Carbon::parse($season->getRawOriginal('start'))->addDay()->addHours(16));

        try {
            $expansionService = new ExpansionService();

            // Act
            $americasSeason = $expansionService->getCurrentSeason($expansion, $americas);
            $europeSeason   = $expansionService->getCurrentSeason($expansion, $europe);

            // Assert
            $this->assertSame($season->id, $americasSeason?->id);
            $this->assertSame($previousSeason->id, $europeSeason?->id);
        } finally {
            Carbon::setTestNow();
        }
    }
}
