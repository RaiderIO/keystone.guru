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
use Tests\TestCases\PublicTestCase;

/**
 * The dungeon context bar follows the current season only. The upcoming season is advertised next to it
 * as a card of its own, but only once it is close enough to its start - seasons are seeded weeks before
 * they start so their mapping can be reviewed, and until then they should not show up at all (#3761).
 */
#[Group('ViewComposers')]
#[Group('HeaderComposer')]
final class HeaderComposerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsGuest();

        // ViewService caches the season it hands the composer for an hour in the 'tmp_file' store, which - unlike
        // the array store the tests run on - survives between test runs. Seasons created below would otherwise be
        // invisible to the composer, or worse, stay visible to whatever runs next.
        $this->flushSeasonCaches();
    }

    #[Test]
    public function compose_givenAnUpcomingSeasonWithinTheWindow_setsTheNextSeasonCard(): void
    {
        // Arrange - inside the try so a failure halfway through still cleans up
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek());

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

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
    public function compose_givenAnUpcomingSeasonBeyondTheWindow_omitsTheNextSeasonCard(): void
    {
        // Arrange - the situation a season dry run puts the site in: seeded, but months out
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeeks(8));

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertSame($upcomingSeason->id, $data['nextSeason']?->id, 'The season should still be found, just not advertised');
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
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek());

            $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

            $this->assertFalse((bool)$classicEra->has_seasons, 'Classic Era must be a game version without seasons');

            $user = User::factory()->create(['game_version_id' => $classicEra->id]);
            $this->actingAs($user);

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertSame($upcomingSeason->id, $data['nextSeason']?->id, 'The season should still be found, just not advertised');
            $this->assertNull($data['dungeonContextNextSeason']);
            $this->assertNull($data['dungeonContextNextSeasonLink']);
        } finally {
            $user?->delete();

            if ($upcomingSeason !== null) {
                $this->deleteSeason($upcomingSeason);
            }
        }
    }

    /**
     * The window is configuration, not a constant - the same season that is too far out by default is
     * advertised once the window is widened past it.
     */
    #[Test]
    public function compose_givenAWidenedWindow_setsTheNextSeasonCardForASeasonThatWasBeyondIt(): void
    {
        // Arrange
        $upcomingSeason = null;

        try {
            $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeeks(8));

            config(['keystoneguru.season.upcoming_visible_days' => 90]);

            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $this->assertSame($upcomingSeason->id, $view->getData()['dungeonContextNextSeason']?->id);
        } finally {
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

    private function createUpcomingSeason(Carbon $start): Season
    {
        $expansion = Expansion::firstWhere('shortname', Expansion::EXPANSION_MIDNIGHT);

        return Season::create([
            'expansion_id'            => $expansion->id,
            'seasonal_affix_id'       => null,
            'index'                   => 2,
            'start'                   => $start->toDateTimeString(),
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
