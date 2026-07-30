<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\SeasonDungeon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The dungeon selection keeps opening on the current season's tab while an upcoming season is only seeded,
 * not started (#3761). The upcoming season still gets a tab of its own - it is just no longer selected for
 * you, unless it was explicitly asked for, which is what the header's "next season" card links to.
 */
#[Group('Controller')]
#[Group('DungeonExplore')]
final class DungeonExploreControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsGuest();

        // ViewService caches the seasons it hands the composers for an hour in the 'tmp_file' store, which -
        // unlike the array store the tests run on - survives between test runs
        $this->flushSeasonCaches();
    }

    #[Test]
    public function select_givenAnUpcomingSeason_opensOnTheCurrentSeasonsTab(): void
    {
        // Arrange - inside the try so a failure halfway through still cleans up
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason();

            // Act
            $response = $this->get(route('dungeon.explore.gameversion.select', [
                'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
            ]));

            // Assert
            $response->assertOk();

            $this->assertSeasonTabActive($response->content(), Season::SEASON_MIDNIGHT_S1);
            $this->assertSeasonTabInactive($response->content(), $upcomingSeason->id);
        } finally {
            if ($upcomingSeason !== null) {
                $this->deleteUpcomingSeason($upcomingSeason);
            }
        }
    }

    #[Test]
    public function select_givenASeasonParameter_opensOnThatSeasonsTab(): void
    {
        // Arrange - inside the try so a failure halfway through still cleans up
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason();

            // Act
            $response = $this->get(route('dungeon.explore.gameversion.select', [
                'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
                'season'      => $upcomingSeason->id,
            ]));

            // Assert
            $response->assertOk();

            $this->assertSeasonTabActive($response->content(), $upcomingSeason->id);
            $this->assertSeasonTabInactive($response->content(), Season::SEASON_MIDNIGHT_S1);
        } finally {
            if ($upcomingSeason !== null) {
                $this->deleteUpcomingSeason($upcomingSeason);
            }
        }
    }

    private function assertSeasonTabActive(string $content, int $seasonId): void
    {
        $this->assertStringContainsString('active', $this->getSeasonTabClasses($content, $seasonId));
    }

    private function assertSeasonTabInactive(string $content, int $seasonId): void
    {
        $this->assertStringNotContainsString('active', $this->getSeasonTabClasses($content, $seasonId));
    }

    private function getSeasonTabClasses(string $content, int $seasonId): string
    {
        $matches = [];

        $this->assertSame(
            1,
            preg_match(sprintf('/id="season-%d-grid-tab"\s+class="([^"]*)"/', $seasonId), $content, $matches),
            sprintf('Expected a tab for season %d to be rendered', $seasonId),
        );

        return $matches[1];
    }

    /**
     * A season that is seeded but has not started yet - the situation a season dry run puts the site in.
     */
    private function createUpcomingSeason(): Season
    {
        $expansion = Expansion::firstWhere('shortname', Expansion::EXPANSION_MIDNIGHT);

        $season = Season::create([
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => null,
            'index'                   => 2,
            'start'                   => Carbon::now()->addWeeks(8)->toDateTimeString(),
            'presets'                 => 0,
            'affix_group_count'       => 8,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 25,
            'item_level_min'          => 240,
            'item_level_max'          => 300,
        ]);

        SeasonDungeon::create([
            'season_id'  => $season->id,
            'dungeon_id' => Dungeon::firstWhere('key', Dungeon::DUNGEON_ARA_KARA_CITY_OF_ECHOES)->id,
        ]);

        return $season;
    }

    /**
     * Through the query builder on purpose - SeederModel silently blocks deleting these models for anyone who is
     * not an admin, so $model->delete() would leave the rows behind. That skips the model events too, hence
     * flushing the caches keyed on seasons by hand.
     */
    private function deleteUpcomingSeason(Season $season): void
    {
        SeasonDungeon::query()->where('season_id', $season->id)->delete();
        Season::query()->whereKey($season->id)->delete();

        $this->flushSeasonCaches();
    }

    private function flushSeasonCaches(): void
    {
        new SeasonDungeon()->flushCache();
        new Season()->flushCache();
        Cache::store('tmp_file')->flush();
    }
}
