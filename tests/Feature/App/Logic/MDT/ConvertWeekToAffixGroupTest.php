<?php

namespace Tests\Feature\App\Logic\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * mdtWeek 0 is a legitimate week, not a "not provided" sentinel: seasons.start_affix_group_index
     * is documented as the 0-based offset that week 0 resolves to. For a season whose rotation starts
     * at index 0 the non-TWW_S1 offset makes the raw index -1, which must wrap to the end of the
     * rotation rather than miss the collection and log an error (Sentry PHP-LARAVEL-TV).
     *
     * @throws Exception
     */
    #[Test]
    public function convertWeekToAffixGroup_GivenWeekZeroOnSeasonStartingAtIndexZero_ShouldWrapToLastAffixGroupWithoutLoggingAnError(): void
    {
        // Arrange - Shadowlands S4 starts its rotation at index 0, so week 0 computes a raw index of -1
        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $season  = Season::with('affixGroups')->findOrFail(Season::SEASON_SL_S4);

        $this->assertEquals(0, $season->start_affix_group_index, 'Fixture assumption: SL S4 starts at rotation index 0');

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeasonForDungeon')->willReturn($season);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($season);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn($season);

        Log::shouldReceive('error')->never();

        // Act
        $affixGroup = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, 0);

        // Assert - week 0 wraps to the end of the rotation, the week before week 1
        $this->assertNotNull($affixGroup);
        $this->assertEquals($season->affixGroups->last()->id, $affixGroup->id);
    }

    /**
     * Week 1 must still resolve to the season's configured starting affix group - the wrap-around
     * added for week 0 must not shift the rest of the rotation.
     *
     * @throws Exception
     */
    #[Test]
    public function convertWeekToAffixGroup_GivenWeekOneOnSeasonStartingAtIndexZero_ShouldResolveToTheStartingAffixGroup(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $season  = Season::with('affixGroups')->findOrFail(Season::SEASON_SL_S4);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeasonForDungeon')->willReturn($season);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($season);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn($season);

        // Act
        $affixGroup = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, 1);

        // Assert
        $this->assertNotNull($affixGroup);
        $this->assertEquals($season->affixGroups->get($season->start_affix_group_index)->id, $affixGroup->id);
    }

    /**
     * TWW S1 has its own offset (no -1) and already resolved week 0 without wrapping - it must keep
     * resolving to the season's starting affix group.
     *
     * @throws Exception
     */
    #[Test]
    public function convertWeekToAffixGroup_GivenWeekZeroOnTwwSeasonOne_ShouldResolveToTheStartingAffixGroup(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $season  = Season::with('affixGroups')->findOrFail(Season::SEASON_TWW_S1);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeasonForDungeon')->willReturn($season);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($season);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn($season);

        // Act
        $affixGroup = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, 0);

        // Assert
        $this->assertNotNull($affixGroup);
        $this->assertEquals($season->affixGroups->get($season->start_affix_group_index)->id, $affixGroup->id);
    }

    /**
     * Every week of a rotation must resolve to a distinct affix group - a week that silently
     * collapses onto another week's affixes is the failure mode this whole path guards against.
     *
     * @throws Exception
     */
    #[Test]
    public function convertWeekToAffixGroup_GivenEveryWeekOfARotation_ShouldResolveToDistinctAffixGroups(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $season  = Season::with('affixGroups')->findOrFail(Season::SEASON_SL_S4);

        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeasonForDungeon')->willReturn($season);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($season);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn($season);

        // Act
        $resolvedIds = [];
        foreach (range(0, $season->affixGroups->count() - 1) as $mdtWeek) {
            $resolvedIds[] = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, $mdtWeek)?->id;
        }

        // Assert
        $this->assertNotContains(null, $resolvedIds);
        $this->assertCount($season->affixGroups->count(), array_unique($resolvedIds));
    }

    /**
     * Exporting an affix group to an MDT week and importing that week again must land on the same
     * affix group, for every affix group of every seeded season - including a season whose seeded
     * affix groups are fewer than its declared affix_group_count.
     *
     * @throws Exception
     */
    #[Test]
    public function convertAffixGroupToWeek_givenEveryAffixGroupOfEverySeason_returnsWeekThatImportsToSameAffixGroup(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $seasons = Season::with('affixGroups')->get();

        $this->assertNotEmpty($seasons);

        foreach ($seasons as $season) {
            $seasonService = $this->createSeasonServicePinnedTo($season);

            foreach ($season->affixGroups as $affixGroup) {
                // Act
                $week          = Conversion::convertAffixGroupToWeek(AffixGroup::findOrFail($affixGroup->id));
                $importedGroup = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, $week);

                // Assert
                $this->assertSame(
                    $affixGroup->id,
                    $importedGroup?->id,
                    sprintf('Season %d: affix group %d exported as week %d', $season->id, $affixGroup->id, $week),
                );
            }
        }
    }

    /**
     * Importing an MDT week and exporting the resulting affix group must return the same week, for
     * every week of every seeded season's rotation (1..count, or 0..count-1 for TWW S1).
     *
     * @throws Exception
     */
    #[Test]
    public function convertWeekToAffixGroup_givenEveryWeekOfEverySeason_returnsAffixGroupThatExportsToSameWeek(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $seasons = Season::with('affixGroups')->get();

        foreach ($seasons as $season) {
            $seasonService = $this->createSeasonServicePinnedTo($season);
            $firstWeek     = $season->id === Season::SEASON_TWW_S1 ? 0 : 1;

            foreach (range($firstWeek, $firstWeek + $season->affixGroups->count() - 1) as $mdtWeek) {
                // Act
                $affixGroup = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, $mdtWeek);

                // Assert
                $this->assertNotNull($affixGroup, sprintf('Season %d: week %d', $season->id, $mdtWeek));
                $this->assertSame(
                    $mdtWeek,
                    Conversion::convertAffixGroupToWeek(AffixGroup::findOrFail($affixGroup->id)),
                    sprintf('Season %d: week %d imported as affix group %d', $season->id, $mdtWeek, $affixGroup->id),
                );
            }
        }
    }

    /**
     * The season's starting affix group is week 1 (week 0 for TWW S1), and the affix group before it
     * in the rotation is the last week.
     */
    #[Test]
    #[DataProvider('convertAffixGroupToWeek_givenSeasonStartingAffixGroup_returnsFirstWeekDataProvider')]
    public function convertAffixGroupToWeek_givenSeasonStartingAffixGroup_returnsFirstWeek(
        int $seasonId,
        int $expectedFirstWeek,
    ): void {
        // Arrange
        $season        = Season::with('affixGroups')->findOrFail($seasonId);
        $count         = $season->affixGroups->count();
        $startingGroup = $season->affixGroups->get($season->start_affix_group_index);
        $previousGroup = $season->affixGroups->get(($season->start_affix_group_index + $count - 1) % $count);

        // Act
        $startingWeek = Conversion::convertAffixGroupToWeek(AffixGroup::findOrFail($startingGroup->id));
        $previousWeek = Conversion::convertAffixGroupToWeek(AffixGroup::findOrFail($previousGroup->id));

        // Assert
        $this->assertSame($expectedFirstWeek, $startingWeek);
        $this->assertSame($expectedFirstWeek + $count - 1, $previousWeek);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function convertAffixGroupToWeek_givenSeasonStartingAffixGroup_returnsFirstWeekDataProvider(): array
    {
        return [
            'Shadowlands S4, starts at index 0'    => [Season::SEASON_SL_S4, 1],
            'The War Within S1, starts at index 3' => [Season::SEASON_TWW_S1, 0],
        ];
    }

    /**
     * Week 0 on a season whose weeks run 1..count is the same rotation slot as week count; exporting the
     * affix group it imports as yields that canonical week.
     *
     * @throws Exception
     */
    #[Test]
    public function convertAffixGroupToWeek_givenAffixGroupImportedFromWeekZero_returnsLastWeekOfRotation(): void
    {
        // Arrange
        $dungeon       = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $season        = Season::with('affixGroups')->findOrFail(Season::SEASON_SL_S4);
        $seasonService = $this->createSeasonServicePinnedTo($season);
        $affixGroup    = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, 0);
        $this->assertNotNull($affixGroup);

        // Act
        $week = Conversion::convertAffixGroupToWeek(AffixGroup::findOrFail($affixGroup->id));

        // Assert
        $this->assertSame($season->affixGroups->count(), $week);
    }

    /**
     * The season relation is loaded by the conversion itself, so an affix group fetched on its own
     * does not trip lazy loading (which throws under the test configuration).
     */
    #[Test]
    public function convertAffixGroupToWeek_givenSeasonRelationNotLoaded_returnsWeekWithoutLazyLoading(): void
    {
        // Arrange
        $affixGroupId = Season::with('affixGroups')->findOrFail(Season::SEASON_SL_S4)->affixGroups->first()->id;
        $affixGroup   = AffixGroup::findOrFail($affixGroupId);
        $this->assertFalse($affixGroup->relationLoaded('season'));

        // Act
        $week = Conversion::convertAffixGroupToWeek($affixGroup);

        // Assert
        $this->assertSame(1, $week);
    }

    /**
     * An affix group missing from its (already loaded) season's affix groups cannot be positioned in
     * the rotation - log it and fall back to the season's first week rather than throw.
     */
    #[Test]
    public function convertAffixGroupToWeek_givenAffixGroupMissingFromLoadedSeasonAffixGroups_returnsFirstWeekAndLogsError(): void
    {
        // Arrange
        $season     = Season::findOrFail(Season::SEASON_SL_S4);
        $affixGroup = AffixGroup::where('season_id', $season->id)->firstOrFail();
        $season->setRelation('affixGroups', new Collection());
        $affixGroup->setRelation('season', $season);

        Log::shouldReceive('error')->once();

        // Act
        $week = Conversion::convertAffixGroupToWeek($affixGroup);

        // Assert
        $this->assertSame(1, $week);
    }

    /**
     * A season without any seeded affix groups cannot resolve a week; return null so the importer
     * falls back to the current affix group.
     *
     * @throws Exception
     */
    #[Test]
    public function convertWeekToAffixGroup_givenSeasonWithoutAffixGroups_returnsNullAndLogsError(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', 'mechagonjunkyard')->firstOrFail();
        $season  = new Season([
            'start_affix_group_index' => 0,
            'affix_group_count'       => 12,
        ]);
        $season->setRelation('affixGroups', new Collection());
        $seasonService = $this->createSeasonServicePinnedTo($season);

        Log::shouldReceive('error')->once();

        // Act
        $affixGroup = Conversion::convertWeekToAffixGroup($seasonService, $dungeon, 1);

        // Assert
        $this->assertNull($affixGroup);
    }

    /**
     * @throws Exception
     */
    private function createSeasonServicePinnedTo(Season $season): SeasonServiceInterface
    {
        $seasonService = $this->createMock(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeasonForDungeon')->willReturn($season);
        $seasonService->method('getUpcomingSeasonForDungeon')->willReturn($season);
        $seasonService->method('getMostRecentSeasonForDungeon')->willReturn($season);

        return $seasonService;
    }
}
