<?php

namespace Tests\Feature\App\Logic\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Affix;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

#[Group('MDT')]
final class ConvertWeekToAffixGroupTest extends TestCase
{
    /**
     * End-to-end through the real SeasonService: on 2022-09-01 Shadowlands S4 is the current season
     * and genuinely contains Operation Mechagon: Junkyard, so its MDT week must resolve to a
     * Shrouded (SL S4) affix group rather than the dungeon's other historical season (BFA S4,
     * Awakened). This is the faithful reproduction of the reported issue.
     */
    #[Test]
    public function convertWeekToAffixGroup_GivenDungeonInCurrentSeason_ShouldResolveAgainstCurrentSeason(): void
    {
        // Arrange - Shadowlands S4 is active on 2022-09-01 and contains Junkyard
        $this->travelTo(Carbon::create(2022, 9, 1));

        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();

        // Act
        $affixGroup = Conversion::convertWeekToAffixGroup(app(SeasonServiceInterface::class), $dungeon, 1);

        // Assert
        $this->assertNotNull($affixGroup);
        $this->assertEquals(Season::SEASON_SL_S4, $affixGroup->season_id);
        $this->assertTrue($affixGroup->hasAffix(Affix::AFFIX_SHROUDED));
        $this->assertFalse($affixGroup->hasAffix(Affix::AFFIX_AWAKENED));
    }

    /**
     * When the season service resolves a current season for the dungeon, that season must be used
     * even when the dungeon's upcoming/most-recent season (the historical fallback) would resolve
     * to a different, wrong seasonal affix.
     *
     * @throws Exception
     */
    #[Test]
    public function convertWeekToAffixGroup_GivenServiceResolvesCurrentSeason_ShouldPreferItOverFallbacks(): void
    {
        // Arrange - Operation Mechagon: Junkyard exists in both BFA S4 (Awakened) and SL S4 (Shrouded)
        $dungeon        = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $currentSeason  = Season::findOrFail(Season::SEASON_SL_S4);
        $fallbackSeason = Season::findOrFail(Season::SEASON_BFA_S4);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeasonForDungeon')->with($dungeon)->willReturn($currentSeason);
        // The historical fallbacks resolve to the wrong (Awakened) season - they must not win.
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($fallbackSeason);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn($fallbackSeason);

        // Act
        $affixGroup = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, 1);

        // Assert
        $this->assertNotNull($affixGroup);
        $this->assertEquals(Season::SEASON_SL_S4, $affixGroup->season_id);
        $this->assertTrue($affixGroup->hasAffix(Affix::AFFIX_SHROUDED));
        $this->assertFalse($affixGroup->hasAffix(Affix::AFFIX_AWAKENED));
    }

    /**
     * A dungeon that is not part of the current season (such as a legacy dungeon) has no current
     * season, so the MDT week must fall back to the dungeon's most-recent season.
     *
     * @throws Exception
     */
    #[Test]
    public function convertWeekToAffixGroup_GivenDungeonNotInCurrentSeason_ShouldFallBackToMostRecentSeason(): void
    {
        // Arrange
        $dungeon          = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $mostRecentSeason = Season::findOrFail(Season::SEASON_SL_S4);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeasonForDungeon')->willReturn(null);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn(null);
        $seasonService->method('getMostRecentSeasonForDungeon')->with($dungeon)->willReturn($mostRecentSeason);

        // Act
        $affixGroup = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, 1);

        // Assert
        $this->assertNotNull($affixGroup);
        $this->assertEquals(Season::SEASON_SL_S4, $affixGroup->season_id);
        $this->assertTrue($affixGroup->hasAffix(Affix::AFFIX_SHROUDED));
    }
}
