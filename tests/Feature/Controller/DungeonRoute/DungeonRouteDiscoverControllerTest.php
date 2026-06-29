<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Features\DungeonOverview;
use App\Features\NpcCompendium;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Discover')]
final class DungeonRouteDiscoverControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::findOrFail(1));
        Feature::define(NpcCompendium::class, true);
    }

    #[Test]
    public function discoverDungeon_givenFeatureActive_returnsDungeonOverview(): void
    {
        // Arrange
        Feature::define(DungeonOverview::class, true);
        $gameVersion = GameVersion::getDefaultGameVersion();
        $dungeon     = $this->getDungeonWithNonFacadeFloor(
            fn(Builder $query) => $query->active()->forGameVersion($gameVersion),
        );

        // Act
        $response = $this->get(route('dungeonroutes.discoverdungeon', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertOk();
        $response->assertSee(__('view_dungeonroute.discover.dungeon.overview.compendium.title'));
        $response->assertSee(route('compendium.activity', ['dungeon' => $dungeon]));
    }

    #[Test]
    public function discoverDungeon_givenFeatureInactive_returnsLegacyOverview(): void
    {
        // Arrange
        Feature::define(DungeonOverview::class, false);
        $gameVersion = GameVersion::getDefaultGameVersion();
        $dungeon     = $this->getDungeonWithNonFacadeFloor(
            fn(Builder $query) => $query->active()->forGameVersion($gameVersion),
        );

        // Act
        $response = $this->get(route('dungeonroutes.discoverdungeon', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertOk();
        $response->assertDontSee(__('view_dungeonroute.discover.dungeon.overview.compendium.title'));
    }
}
