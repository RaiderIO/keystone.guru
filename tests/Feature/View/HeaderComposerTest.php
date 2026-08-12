<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\IsolatesSeededUpcomingSeasons;
use Tests\TestCases\PublicTestCase;

/**
 * The dungeon context bar follows the current season only. The upcoming season is advertised next to it
 * as a card of its own, and in the header nav link, but only once an admin has marked it `active` -
 * seasons are seeded weeks before they start so their mapping can be reviewed, and until then they should
 * not show up anywhere (#3761, #3868).
 */
#[Group('ViewComposers')]
#[Group('HeaderComposer')]
final class HeaderComposerTest extends PublicTestCase
{
    use IsolatesSeededUpcomingSeasons;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsGuest();

        // ViewService caches the season it hands the composer for an hour in the 'tmp_file' store, which - unlike
        // the array store the tests run on - survives between test runs. Seasons created below would otherwise be
        // invisible to the composer, or worse, stay visible to whatever runs next.
        $this->flushSeasonCaches();

        // These tests assert on which season is upcoming site-wide, so a seeded upcoming season would answer
        // for them - and no assertion of "nothing is advertised" could hold at all.
        $this->hideSeededUpcomingSeasons();
    }

    #[Test]
    public function compose_givenAnActiveUpcomingSeason_setsTheNextSeasonAndCard(): void
    {
        // Arrange - inside the try so a failure halfway through still cleans up
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek(), active: true);

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertSame($upcomingSeason->id, $data['nextSeason']?->id);
            $this->assertNotNull($data['dungeonContextNextSeason']);
            $this->assertSame($upcomingSeason->id, $data['dungeonContextNextSeason']->id);
            $this->assertStringContainsString(sprintf('season=%d', $upcomingSeason->id), $data['dungeonContextNextSeasonLink']);
        } finally {
            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    #[Test]
    public function compose_givenAnInactiveUpcomingSeason_omitsTheNextSeasonEverywhere(): void
    {
        // Arrange - the situation a season dry run puts the site in: seeded, but not ready to reveal
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek(), active: false);

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertNull($data['nextSeason'], 'An inactive season must not leak into the header nav link either');
            $this->assertNull($data['dungeonContextNextSeason']);
            $this->assertNull($data['dungeonContextNextSeasonLink']);
        } finally {
            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    /**
     * Seasons are a retail concept. The dungeon selection hides every season tab for a game version without
     * them, so advertising an upcoming season there would be a card leading to a page that cannot show it.
     */
    #[Test]
    public function compose_givenAGameVersionWithoutSeasons_omitsTheNextSeasonCard(): void
    {
        // Arrange
        $upcomingSeason = null;
        $user           = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek(), active: true);

            $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

            $this->assertFalse((bool)$classicEra->has_seasons, 'Classic Era must be a game version without seasons');

            $user = User::factory()->create(['game_version_id' => $classicEra->id]);
            $this->actingAs($user);

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertSame($upcomingSeason->id, $data['nextSeason']?->id, 'The season should still be found, just not advertised as a card');
            $this->assertNull($data['dungeonContextNextSeason']);
            $this->assertNull($data['dungeonContextNextSeasonLink']);
        } finally {
            $user?->delete();

            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    private function deleteSeason(Season $season): void
    {
        $season->delete();

        $this->flushSeasonCaches();
    }

    /**
     * The model events take care of laravel-model-caching; ViewService's 'tmp_file' store is a plain file cache
     * that nothing invalidates, and it holds the season for an hour.
     */
    private function flushSeasonCaches(): void
    {
        Cache::store('tmp_file')->flush();
    }

    private function createUpcomingSeason(Carbon $start, bool $active): Season
    {
        $expansion = Expansion::firstWhere('shortname', Expansion::EXPANSION_MIDNIGHT);

        return Season::create([
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => null,
            'index'                   => 2,
            'start'                   => $start->toDateTimeString(),
            'active'                  => $active,
            'presets'                 => 0,
            'affix_group_count'       => 8,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 25,
            'item_level_min'          => 240,
            'item_level_max'          => 300,
        ]);
    }
}
