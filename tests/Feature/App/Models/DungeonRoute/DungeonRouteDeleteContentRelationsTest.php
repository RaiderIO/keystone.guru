<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\DungeonRoute\DungeonRouteRating;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Polyline;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * Covers DungeonRoute::deleteContentRelations(), which was extracted out of the deleting hook so that
 * the delete path and DungeonRouteUpgradeDraftService::apply() cannot drift apart.
 */
#[Group('DungeonRoute')]
class DungeonRouteDeleteContentRelationsTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function deleteContentRelations_givenRouteWithTeam_doesNotDeleteTeamMapIcons(): void
    {
        // Arrange
        $owner = null;
        $team  = null;
        $route = null;

        try {
            $dungeon = $this->getDungeonWithNonFacadeFloor();
            $floor   = $dungeon->floors()->where('facade', 0)->firstOrFail();

            $owner = User::factory()->create();
            $team  = Team::create([
                'public_key'  => Team::generateRandomPublicKey(),
                'name'        => 'Upgrade draft test team',
                'description' => 'Upgrade draft test team',
            ]);

            $route = DungeonRoute::factory()->create([
                'author_id'  => $owner->id,
                'dungeon_id' => $dungeon->id,
                'team_id'    => $team->id,
                'expires_at' => null,
            ]);

            // An icon the route owns, and an icon that belongs to the team but not to this route
            $routeMapIcon = MapIcon::factory()->create([
                'dungeon_route_id'   => $route->id,
                'mapping_version_id' => null,
                'floor_id'           => $floor->id,
                'team_id'            => null,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            ]);
            $teamMapIcon = MapIcon::factory()->create([
                'dungeon_route_id'   => null,
                'mapping_version_id' => null,
                'floor_id'           => $floor->id,
                'team_id'            => $team->id,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            ]);

            // Act
            $route->deleteContentRelations();

            // Assert
            $this->assertNull(MapIcon::find($routeMapIcon->id), 'The route\'s own map icon should be deleted');
            $this->assertNotNull(MapIcon::find($teamMapIcon->id), 'A team wide map icon must never be deleted by the route');

            $teamMapIcon->delete();
        } finally {
            $route?->delete();
            $team?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function deleteContentRelations_givenRouteWithBrushlines_deletesPolylines(): void
    {
        // Arrange
        $owner = null;
        $route = null;

        try {
            $dungeon = $this->getDungeonWithNonFacadeFloor();
            $floor   = $dungeon->floors()->where('facade', 0)->firstOrFail();

            $owner = User::factory()->create();
            $route = DungeonRoute::factory()->create([
                'author_id'  => $owner->id,
                'dungeon_id' => $dungeon->id,
                'expires_at' => null,
            ]);

            $brushline = Brushline::create([
                'dungeon_route_id' => $route->id,
                'floor_id'         => $floor->id,
                'polyline_id'      => -1,
            ]);
            $polyline = Polyline::create([
                'model_id'    => $brushline->id,
                'model_class' => Brushline::class,
                'color'       => '#ff0000',
                'weight'      => 2,
                'vertices_json' => '[]',
            ]);
            $brushline->update(['polyline_id' => $polyline->id]);

            // Act
            $route->deleteContentRelations();

            // Assert
            $this->assertNull(Brushline::find($brushline->id), 'The brushline should be deleted');
            $this->assertNull(Polyline::find($polyline->id), 'The brushline\'s polyline should be deleted along with it');
        } finally {
            $route?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function deleteContentRelations_givenRouteWithKillZones_deletesKillZoneEnemies(): void
    {
        // Arrange
        $owner = null;
        $route = null;

        try {
            $dungeon = $this->getDungeonWithNonFacadeFloor();

            $owner = User::factory()->create();
            $route = DungeonRoute::factory()->create([
                'author_id'  => $owner->id,
                'dungeon_id' => $dungeon->id,
                'expires_at' => null,
            ]);

            $killZone = KillZone::create([
                'dungeon_route_id' => $route->id,
                'floor_id'         => null,
                'color'            => '#ff0000',
                'index'            => 1,
            ]);
            $killZoneEnemy = KillZoneEnemy::create([
                'kill_zone_id' => $killZone->id,
                'npc_id'       => 12345,
                'mdt_id'       => 1,
                'enemy_id'     => 99999,
            ]);

            // Act
            $route->deleteContentRelations();

            // Assert
            $this->assertNull(KillZone::find($killZone->id), 'The kill zone should be deleted');
            $this->assertNull(KillZoneEnemy::find($killZoneEnemy->id), 'The kill zone\'s enemies should be deleted along with it');
        } finally {
            $route?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function delete_givenOriginalWithDraft_deletesDraft(): void
    {
        // Arrange
        $owner    = null;
        $original = null;
        $draftId  = null;

        try {
            $owner    = User::factory()->create();
            $original = DungeonRoute::factory()->create([
                'author_id'  => $owner->id,
                'expires_at' => null,
            ]);

            $draft = DungeonRoute::factory()->create([
                'author_id'                   => $owner->id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
            ]);
            $draftId = $draft->id;

            // Act
            $original->delete();
            $original = null;

            // Assert
            $this->assertNull(DungeonRoute::find($draftId), 'Deleting an original must delete its upgrade draft');
        } finally {
            if ($draftId !== null) {
                DungeonRoute::find($draftId)?->delete();
            }
            $original?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function delete_givenRoute_stillDeletesTagsRatingsAndFavorites(): void
    {
        // Arrange
        $owner   = null;
        $route   = null;
        $routeId = null;

        try {
            $owner   = User::factory()->create();
            $route   = DungeonRoute::factory()->create([
                'author_id'  => $owner->id,
                'expires_at' => null,
            ]);
            $routeId = $route->id;

            $tagCategory = TagCategory::firstWhere('name', TagCategory::DUNGEON_ROUTE_PERSONAL);
            $tag         = Tag::create([
                'tag_category_id' => $tagCategory->id,
                'model_id'        => $route->id,
                'model_class'     => DungeonRoute::class,
                'user_id'         => $owner->id,
                'name'            => 'upgrade-draft-test',
                'color'           => '#ff0000',
            ]);
            $rating = DungeonRouteRating::create([
                'dungeon_route_id' => $route->id,
                'user_id'          => $owner->id,
                'rating'           => 5,
            ]);
            $favorite = DungeonRouteFavorite::create([
                'dungeon_route_id' => $route->id,
                'user_id'          => $owner->id,
            ]);

            // Act
            $route->delete();
            $route = null;

            // Assert - the extraction of deleteContentRelations() must not have dropped these
            $this->assertNull(Tag::find($tag->id), 'Tags should still be deleted with the route');
            $this->assertNull(DungeonRouteRating::find($rating->id), 'Ratings should still be deleted with the route');
            $this->assertNull(DungeonRouteFavorite::find($favorite->id), 'Favorites should still be deleted with the route');
            $this->assertNull(DungeonRoute::find($routeId), 'The route itself should be deleted');
        } finally {
            $route?->delete();
            $owner?->delete();
        }
    }
}
