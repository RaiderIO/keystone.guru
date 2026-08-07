<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\Expansion;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * The Create Route dungeon-selection popup is composed by two separate view composers that both
 * read the next season independently (CreateRouteFormComposer for the page itself,
 * DungeonSelectComposer for the included `common.dungeon.select` partial) - a fix that only gates
 * one of them leaves the season's dungeons selectable through the other (#3868 cold review).
 */
#[Group('DungeonRouteController')]
#[Group('CreateRouteSeasonGate')]
final class DungeonRouteControllerCreateSeasonGateTest extends DungeonRouteControllerCreateTestBase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsGuest();

        // ViewService caches the season it hands the composers for an hour in the 'tmp_file' store, which -
        // unlike the array store the tests run on - survives between test runs.
        Cache::store('tmp_file')->flush();
    }

    #[Test]
    public function create_givenAnInactiveUpcomingSeason_omitsItsDungeonOptgroup(): void
    {
        // Arrange
        $upcomingSeason = $this->createUpcomingSeason(active: false);

        try {
            // Act
            $response = $this->get(route('dungeonroute.new'));

            // Assert - name_long always contains name ("Season :index"), so this holds regardless of
            // whether the short or long label is used for this expansion pairing
            $response->assertOk();
            $response->assertDontSee($upcomingSeason->name, false);
        } finally {
            $upcomingSeason->seasonDungeons()->get()->each->delete();
            $upcomingSeason->delete();
            Cache::store('tmp_file')->flush();
        }
    }

    #[Test]
    public function create_givenAnActiveUpcomingSeason_showsItsDungeonOptgroup(): void
    {
        // Arrange
        $upcomingSeason = $this->createUpcomingSeason(active: true);

        try {
            // Act
            $response = $this->get(route('dungeonroute.new'));

            // Assert
            $response->assertOk();
            $response->assertSee($upcomingSeason->name, false);
        } finally {
            $upcomingSeason->seasonDungeons()->get()->each->delete();
            $upcomingSeason->delete();
            Cache::store('tmp_file')->flush();
        }
    }

    private function createUpcomingSeason(bool $active): Season
    {
        $expansion = Expansion::firstWhere('shortname', Expansion::EXPANSION_MIDNIGHT);
        $dungeon   = $this->getActiveDungeon();

        $season = Season::create([
            'expansion_id'      => $expansion->id,
            'seasonal_affix_id' => null,
            // A deliberately unusual index, so its rendered "Season 9999" label can't collide with a
            // real season's label elsewhere on the page.
            'index'                   => 9999,
            'start'                   => Carbon::now()->addWeek()->toDateTimeString(),
            'active'                  => $active,
            'presets'                 => 0,
            'affix_group_count'       => 8,
            'start_affix_group_index' => 0,
            'key_level_min'           => 2,
            'key_level_max'           => 25,
            'item_level_min'          => 240,
            'item_level_max'          => 300,
        ]);
        $season->syncDungeons([$dungeon->id]);

        return $season;
    }
}
