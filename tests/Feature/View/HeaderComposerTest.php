<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Expansion;
use App\Models\Season;
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
        // Arrange
        $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeek());

        try {
            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertNotNull($data['dungeonContextNextSeason']);
            $this->assertSame($upcomingSeason->id, $data['dungeonContextNextSeason']->id);
            $this->assertStringContainsString(sprintf('season=%d', $upcomingSeason->id), $data['dungeonContextNextSeasonLink']);
        } finally {
            $this->deleteSeason($upcomingSeason);
        }
    }

    #[Test]
    public function compose_givenAnUpcomingSeasonBeyondTheWindow_omitsTheNextSeasonCard(): void
    {
        // Arrange - the situation a season dry run puts the site in: seeded, but months out
        $upcomingSeason = $this->createUpcomingSeason(Carbon::now()->addWeeks(8));

        try {
            $view = view('common.layout.header');

            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $data = $view->getData();

            $this->assertSame($upcomingSeason->id, $data['nextSeason']?->id, 'The season should still be found, just not advertised');
            $this->assertNull($data['dungeonContextNextSeason']);
            $this->assertNull($data['dungeonContextNextSeasonLink']);
        } finally {
            $this->deleteSeason($upcomingSeason);
        }
    }

    /**
     * Removes a season created by this test. Through the query builder on purpose - SeederModel silently blocks
     * deleting a Season for anyone who is not an admin, so $season->delete() would leave the row behind. That does
     * mean no model events fire, so the caches keyed on seasons are flushed by hand.
     */
    private function deleteSeason(Season $season): void
    {
        Season::query()->whereKey($season->id)->delete();

        $this->flushSeasonCaches();
    }

    private function flushSeasonCaches(): void
    {
        new Season()->flushCache();
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
