<?php

namespace Tests\Feature\View\Common\Dungeon;

use App\Models\AffixGroup\AffixGroup;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonList')]
final class DungeonListTest extends PublicTestCase
{
    private function nextSeason(): Season
    {
        return Season::findOrFail(Season::SEASON_MIDNIGHT_S1);
    }

    private function nextSeasonLink(): string
    {
        return route('dungeon.explore.gameversion.select', [
            'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
            'season'      => $this->nextSeason()->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseParams(): array
    {
        return [
            'gameVersion'     => GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL),
            'dungeons'        => collect(),
            'useAbbreviation' => true,
            'selectable'      => true,
            'showMore'        => false,
        ];
    }

    /**
     * Scenario: the next season is close enough to its start to be advertised, so it gets a card of its own
     * next to the current season's dungeons rather than replacing them (#3761).
     */
    #[Test]
    public function render_givenAnUpcomingSeason_showsTheNextSeasonCard(): void
    {
        // Act
        $html = view('common.dungeon.list', [
            ...$this->baseParams(),
            'nextSeason'     => $this->nextSeason(),
            'nextSeasonLink' => $this->nextSeasonLink(),
        ])->render();

        // Assert
        $this->assertStringContainsString(__('view_common.dungeon.list.next_season'), $html);
        $this->assertStringContainsString($this->nextSeasonLink(), $html);
    }

    /**
     * Scenario: no next season, or one that is still too far out to advertise - the header shows the current
     * season's dungeons and nothing else.
     */
    #[Test]
    public function render_givenNoUpcomingSeason_omitsTheNextSeasonCard(): void
    {
        // Act
        $html = view('common.dungeon.list', [
            ...$this->baseParams(),
            'nextSeason'     => null,
            'nextSeasonLink' => null,
        ])->render();

        // Assert
        $this->assertStringNotContainsString(__('view_common.dungeon.list.next_season'), $html);
    }

    /**
     * A Blade @include inherits the enclosing scope, so `$thisWeekTier` - assigned per dungeon in the loop -
     * stays set for whatever is rendered after it. Both the "More" and the "next season" card would show the
     * last dungeon's "what's easy this week" badge, which means nothing for a card that is not a dungeon.
     */
    #[Test]
    public function render_givenEaseTiers_showsThemOnDungeonCardsOnly(): void
    {
        // Arrange
        $dungeons   = $this->nextSeason()->dungeons()->get();
        $affixGroup = AffixGroup::firstOrFail();
        $tieredIds  = $dungeons->pluck('id');
        /** @var Collection<int, Collection<int, string>> $easeTiers */
        $easeTiers = collect([$affixGroup->id => $tieredIds->mapWithKeys(static fn(int $id) => [$id => 'S'])]);

        $this->assertGreaterThan(0, $dungeons->count(), 'Need seeded season dungeons to give a tier to');

        // Act - every dungeon has a tier, and both extra cards render alongside them
        $html = view('common.dungeon.list', [
            ...$this->baseParams(),
            'dungeons'          => $dungeons,
            'maxColCount'       => $dungeons->count(),
            'showMore'          => true,
            'links'             => collect(['more' => '/explore/retail/select']),
            'easeTiers'         => $easeTiers,
            'currentAffixGroup' => $affixGroup,
            'nextSeason'        => $this->nextSeason(),
            'nextSeasonLink'    => $this->nextSeasonLink(),
        ])->render();

        // Assert - one badge per dungeon, none for the "More" and "next season" cards
        $this->assertStringContainsString('More', $html);
        $this->assertStringContainsString(__('view_common.dungeon.list.next_season'), $html);
        $this->assertSame(
            $dungeons->count(),
            substr_count($html, 'dungeon_card_tiers'),
            'Ease tier badges must render for dungeon cards only',
        );
    }
}
