<?php

namespace Tests\Feature\View\Common\Dungeon;

use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonList')]
final class DungeonListTest extends PublicTestCase
{
    private const string NEXT_SEASON_LINK = '/explore/retail/select?season=17';

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
        // Arrange
        $nextSeason = Season::findOrFail(Season::SEASON_MIDNIGHT_S1);

        // Act
        $html = view('common.dungeon.list', [
            ...$this->baseParams(),
            'nextSeason'     => $nextSeason,
            'nextSeasonLink' => self::NEXT_SEASON_LINK,
        ])->render();

        // Assert
        $this->assertStringContainsString(__('view_common.dungeon.list.next_season'), $html);
        $this->assertStringContainsString(self::NEXT_SEASON_LINK, $html);
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
        $this->assertStringNotContainsString(self::NEXT_SEASON_LINK, $html);
    }
}
