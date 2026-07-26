<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class DungeonRouteCollectionControllerTest extends PublicTestCase
{
    #[Test]
    public function index_givenFeatureInactive_returnsNotFound(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->deactivate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->get(route('collections.index'));

            // Assert
            $response->assertNotFound();
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function index_givenOwnCollections_listsThem(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id' => $creator->id,
            'name'    => 'ZzTestCollectionOfMine',
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->get(route('collections.index'));

            // Assert
            $response->assertOk();
            $response->assertSee('ZzTestCollectionOfMine');
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenValidPayload_createsTheCollectionWithItsRoutes(): void
    {
        // Arrange
        $creator      = $this->createCreator();
        $dungeonRoute = $this->createRouteFor($creator);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = null;

        try {
            // Act
            $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                'name'            => 'ZzTestWeeklyRoutes',
                'description'     => 'My routes for this week',
                'published_state' => PublishedState::WORLD,
                'dungeon_routes'  => [$dungeonRoute->id],
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRouteCollection = DungeonRouteCollection::query()
                ->where('user_id', $creator->id)
                ->first();

            $this->assertNotNull($dungeonRouteCollection, 'The collection must have been created');
            $this->assertSame('ZzTestWeeklyRoutes', $dungeonRouteCollection->name);
            $this->assertSame(
                PublishedState::ALL[PublishedState::WORLD],
                $dungeonRouteCollection->published_state_id,
            );
            $this->assertNotEmpty($dungeonRouteCollection->public_key, 'A collection needs a public key to be shareable');
            $this->assertSame(
                [$dungeonRoute->id],
                $dungeonRouteCollection->dungeonRoutes->pluck('id')->all(),
            );
        } finally {
            $dungeonRouteCollection?->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $creator->delete();
        }
    }

    /**
     * Collecting somebody else's route would let anyone surface - and link around - a route that
     * is not theirs, so the author constraint is the only thing keeping a collection to its owner.
     */
    #[Test]
    public function savenew_givenARouteOwnedByAnotherUser_failsValidation(): void
    {
        // Arrange
        $creator      = $this->createCreator();
        $someoneElse  = User::factory()->create();
        $foreignRoute = $this->createRouteFor($someoneElse);
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                'name'            => 'ZzTestForeignRoutes',
                'published_state' => PublishedState::WORLD,
                'dungeon_routes'  => [$foreignRoute->id],
            ]);

            // Assert
            $response->assertSessionHasErrors('dungeon_routes.0');
            $this->assertSame(0, DungeonRouteCollection::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $foreignRoute->delete();
            $someoneElse->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenTeamPublishedStateWithoutATeam_failsValidation(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                'name'            => 'ZzTestTeamlessCollection',
                'published_state' => PublishedState::TEAM,
            ]);

            // Assert
            $response->assertSessionHasErrors('team_id');
            $this->assertSame(0, DungeonRouteCollection::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    /**
     * A team the user is not a member of would otherwise be a way to read that team's collections
     * back out of the edit form.
     */
    #[Test]
    public function savenew_givenATeamTheUserIsNotAMemberOf_failsValidation(): void
    {
        // Arrange
        $creator     = $this->createCreator();
        $someoneElse = User::factory()->create();
        $team        = $this->createTeamFor($someoneElse);
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                'name'            => 'ZzTestForeignTeamCollection',
                'published_state' => PublishedState::TEAM,
                'team_id'         => $team->id,
            ]);

            // Assert
            $response->assertSessionHasErrors('team_id');
            $this->assertSame(0, DungeonRouteCollection::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $this->deleteTeam($team);
            $someoneElse->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenACategory_filesTheCollectionUnderIt(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = null;

        try {
            // Act
            $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                'name'            => 'ZzTestCategorisedCollection',
                'published_state' => PublishedState::WORLD,
                'category_id'     => DungeonRouteCollectionCategory::ALL[DungeonRouteCollectionCategory::PUG_FRIENDLY],
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRouteCollection = DungeonRouteCollection::query()
                ->where('user_id', $creator->id)
                ->first();

            $this->assertNotNull($dungeonRouteCollection);
            $this->assertSame(
                DungeonRouteCollectionCategory::ALL[DungeonRouteCollectionCategory::PUG_FRIENDLY],
                $dungeonRouteCollection->dungeon_route_collection_category_id,
            );
        } finally {
            $dungeonRouteCollection?->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenACategoryThatDoesNotExist_failsValidation(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                'name'            => 'ZzTestBogusCategoryCollection',
                'published_state' => PublishedState::WORLD,
                'category_id'     => 99999,
            ]);

            // Assert
            $response->assertSessionHasErrors('category_id');
            $this->assertSame(0, DungeonRouteCollection::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    /**
     * A category is optional, so saving the form with the empty option selected has to actually
     * clear it rather than silently keep the previous one.
     */
    #[Test]
    public function update_givenNoCategory_clearsThePreviousOne(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'                              => $creator->id,
            'dungeon_route_collection_category_id' => DungeonRouteCollectionCategory::ALL[DungeonRouteCollectionCategory::MDI],
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => $dungeonRouteCollection->name,
                    'published_state' => PublishedState::WORLD,
                    'category_id'     => null,
                ],
            );

            // Assert
            $response->assertSessionHasNoErrors();
            $dungeonRouteCollection->refresh();
            $this->assertNull($dungeonRouteCollection->dungeon_route_collection_category_id);
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function view_givenACollectionWithACategory_showsTheCategory(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for(null)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'                              => $creator->id,
            'published_state_id'                   => PublishedState::ALL[PublishedState::WORLD],
            'dungeon_route_collection_category_id' => DungeonRouteCollectionCategory::ALL[DungeonRouteCollectionCategory::BEGINNER],
        ]);

        try {
            // Act
            $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $response->assertSee(__(sprintf(
                'dungeonroutecollectioncategories.%s',
                DungeonRouteCollectionCategory::BEGINNER,
            )));
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for(null)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenValidPayload_replacesTheRoutesOfTheCollection(): void
    {
        // Arrange
        $creator = $this->createCreator();
        $first   = $this->createRouteFor($creator);
        $second  = $this->createRouteFor($creator);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $first->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => 'ZzTestRenamedCollection',
                    'published_state' => PublishedState::WORLD,
                    'dungeon_routes'  => [$second->id],
                ],
            );

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRouteCollection->refresh();
            $this->assertSame('ZzTestRenamedCollection', $dungeonRouteCollection->name);
            $this->assertSame(
                [$second->id],
                $dungeonRouteCollection->dungeonRoutes->pluck('id')->all(),
                'Saving replaces the routes rather than adding to them',
            );
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $second->delete();
            $first->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenAnotherUsersCollection_returnsForbidden(): void
    {
        // Arrange
        $creator = $this->createCreator();
        $viewer  = $this->createCreator();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);

        try {
            // Act
            $response = $this->actingAs($viewer)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => 'ZzTestHijackedCollection',
                    'published_state' => PublishedState::WORLD,
                ],
            );

            // Assert
            $response->assertForbidden();
            $dungeonRouteCollection->refresh();
            $this->assertNotSame('ZzTestHijackedCollection', $dungeonRouteCollection->name);
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
            $creator->delete();
        }
    }

    /**
     * The picker showed nothing for a non-owner admin before this was scoped to the collection's
     * actual owner - regressing this makes an admin's save silently wipe every route in someone
     * else's collection, since the picker would submit an empty selection.
     */
    #[Test]
    public function edit_givenAdminEditingAnotherUsersCollection_showsTheOwnersOwnRoutes(): void
    {
        // Arrange
        $admin        = $this->adminUser();
        $owner        = $this->createCreator();
        $dungeonRoute = $this->createRouteFor($owner);
        Feature::for($admin)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $owner->id]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->actingAs($admin)->get(
                route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            );

            // Assert
            $response->assertOk();
            /** @var Collection<int, DungeonRoute> $ownDungeonRoutes */
            $ownDungeonRoutes = $response->viewData('ownDungeonRoutes');
            $this->assertSame(
                [$dungeonRoute->id],
                $ownDungeonRoutes->pluck('id')->all(),
                "The picker must show the collection owner's routes, not the acting admin's",
            );
        } finally {
            DungeonRouteCollectionRoute::where('dungeon_route_collection_id', $dungeonRouteCollection->id)->delete();
            $dungeonRouteCollection->delete();
            Feature::for($admin)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function update_givenAdminEditingAnotherUsersCollection_keepsTheExistingRouteWhenResubmitted(): void
    {
        // Arrange
        $admin        = $this->adminUser();
        $owner        = $this->createCreator();
        $dungeonRoute = $this->createRouteFor($owner);
        Feature::for($admin)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $owner->id]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->actingAs($admin)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => $dungeonRouteCollection->name,
                    'published_state' => PublishedState::WORLD,
                    'dungeon_routes'  => [$dungeonRoute->id],
                ],
            );

            // Assert
            $response->assertSessionHasNoErrors();
            $dungeonRouteCollection->refresh();
            $this->assertSame(
                [$dungeonRoute->id],
                $dungeonRouteCollection->dungeonRoutes->pluck('id')->all(),
                "An admin saving someone else's collection must not silently wipe its routes",
            );
        } finally {
            DungeonRouteCollectionRoute::where('dungeon_route_collection_id', $dungeonRouteCollection->id)->delete();
            $dungeonRouteCollection->delete();
            Feature::for($admin)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $owner->delete();
        }
    }

    /**
     * Without this, a deleted user's still-shareable collection link 500s instead of 404ing,
     * because the view dereferences the (now missing) owner relation.
     */
    #[Test]
    public function view_givenTheOwningUserWasDeleted_returnsNotFoundInsteadOfServerError(): void
    {
        // Arrange
        $creator      = $this->createCreator();
        $dungeonRoute = $this->createRouteFor($creator, PublishedState::WORLD);
        Feature::for(null)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);
        $publicKey = $dungeonRouteCollection->public_key;

        try {
            // Act
            $creator->delete();
            $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $publicKey]));

            // Assert
            $response->assertNotFound();
            $this->assertNull(
                DungeonRouteCollection::where('public_key', $publicKey)->first(),
                "Deleting the owning user must clean up their collections too",
            );
        } finally {
            Feature::for(null)->forget(CreatorProfiles::class);
            // $creator's deletion already cascaded the collection, its route coupling, and the route
        }
    }

    #[Test]
    public function delete_givenOwnCollection_deletesItAndItsCouplings(): void
    {
        // Arrange
        $creator      = $this->createCreator();
        $dungeonRoute = $this->createRouteFor($creator);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->delete(
                route('collections.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            );

            // Assert
            $response->assertRedirect(route('collections.index'));
            $this->assertNull(DungeonRouteCollection::find($dungeonRouteCollection->id));
            $this->assertSame(
                0,
                DungeonRouteCollectionRoute::where('dungeon_route_collection_id', $dungeonRouteCollection->id)->count(),
                'Deleting a collection must not leave its couplings behind - there are no foreign keys to do it',
            );
        } finally {
            DungeonRouteCollectionRoute::where('dungeon_route_collection_id', $dungeonRouteCollection->id)->delete();
            DungeonRouteCollection::where('id', $dungeonRouteCollection->id)->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function view_givenAWorldPublishedCollection_isVisibleToAGuest(): void
    {
        // Arrange
        $creator      = $this->createCreator();
        $dungeonRoute = $this->createRouteFor($creator, PublishedState::WORLD);
        Feature::for(null)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'name'               => 'ZzTestPublicCollection',
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $response->assertSee('ZzTestPublicCollection');
            /** @var Collection<int, DungeonRoute> $viewDungeonRoutes */
            $viewDungeonRoutes = $response->viewData('dungeonRoutes');
            $this->assertSame(
                [$dungeonRoute->id],
                $viewDungeonRoutes->pluck('id')->all(),
            );
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for(null)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function view_givenAnUnpublishedCollection_returnsForbiddenForOtherUsers(): void
    {
        // Arrange
        $creator = $this->createCreator();
        $viewer  = $this->createCreator();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(
                route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            );

            // Assert
            $response->assertForbidden();
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
            $creator->delete();
        }
    }

    /**
     * Sharing a collection must never publish the routes inside it. If this regresses, putting an
     * unpublished route in a public collection becomes a way to leak it to everyone.
     */
    #[Test]
    public function view_givenAnUnpublishedRouteInAPublicCollection_hidesThatRoute(): void
    {
        // Arrange
        $creator     = $this->createCreator();
        $viewer      = $this->createCreator();
        $published   = $this->createRouteFor($creator, PublishedState::WORLD);
        $unpublished = $this->createRouteFor($creator, PublishedState::UNPUBLISHED);
        Feature::for($viewer)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        foreach ([$published, $unpublished] as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        try {
            // Act
            $response = $this->actingAs($viewer)->get(
                route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            );

            // Assert
            $response->assertOk();
            /** @var Collection<int, DungeonRoute> $viewDungeonRoutes */
            $viewDungeonRoutes = $response->viewData('dungeonRoutes');
            $this->assertSame(
                [$published->id],
                $viewDungeonRoutes->pluck('id')->all(),
                'An unpublished route must stay hidden even inside a public collection',
            );
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $unpublished->delete();
            $published->delete();
            $viewer->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function view_givenATeamPublishedCollection_isVisibleToTeamMembersOnly(): void
    {
        // Arrange
        $creator  = $this->createCreator();
        $member   = $this->createCreator();
        $outsider = $this->createCreator();
        $team     = $this->createTeamFor($creator);
        $team->addMember($member, TeamUser::ROLE_MEMBER);

        Feature::for($member)->activate(CreatorProfiles::class);
        Feature::for($outsider)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'team_id'            => $team->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
        ]);

        try {
            // Act
            $memberResponse = $this->actingAs($member)->get(
                route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            );
            $outsiderResponse = $this->actingAs($outsider)->get(
                route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            );

            // Assert
            $memberResponse->assertOk();
            $outsiderResponse->assertForbidden();
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($outsider)->forget(CreatorProfiles::class);
            Feature::for($member)->forget(CreatorProfiles::class);
            $this->deleteTeam($team);
            $outsider->delete();
            $member->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function view_givenFeatureInactive_returnsNotFound(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for(null)->deactivate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        try {
            // Act
            $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertNotFound();
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for(null)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    private function createCreator(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    private function adminUser(): User
    {
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue(
            $admin->hasRole(Role::ROLE_ADMIN),
            'User id=1 must have the admin role for this test (seed the database).',
        );

        return $admin;
    }

    private function createRouteFor(User $user, string $publishedState = PublishedState::WORLD): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ]);
    }

    private function createTeamFor(User $user): Team
    {
        $team = Team::create([
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'name'         => 'ZzTestCollectionTeam',
            'description'  => '',
            'icon_file_id' => -1,
        ]);

        $team->addMember($user, TeamUser::ROLE_ADMIN);

        return $team;
    }

    /**
     * Team::deleting() walks its members and routes, which lazy loading refuses to hydrate on the
     * fly - so they are loaded up front.
     */
    private function deleteTeam(Team $team): void
    {
        $team->load(['members.patreonAdFreeGiveaway', 'dungeonRoutes']);
        $team->delete();
    }
}
