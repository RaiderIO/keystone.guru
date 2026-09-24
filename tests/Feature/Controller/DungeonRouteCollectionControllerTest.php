<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategoryType;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Support\Carbon;
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
    public function index_givenAWorldPublishedCollection_showsItsVisibility(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'name'               => 'ZzTestCollectionVisibility',
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->get(route('collections.index'));

            // Assert
            $response->assertOk();
            $response->assertSee(e(__('view_collection.index.published_state.world')), false);
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
                'dungeon_routes'  => [$dungeonRoute->public_key],
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

    #[Test]
    public function savenew_givenTheCollectionLimitIsReached_createsNoFurtherCollection(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        DungeonRouteCollection::factory()
            ->count(DungeonRouteCollection::MAX_COLLECTIONS)
            ->create(['user_id' => $creator->id]);

        try {
            // Act
            $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                'name'            => 'ZzTestOneTooMany',
                'published_state' => PublishedState::WORLD,
                'dungeon_routes'  => [],
            ]);

            // Assert
            $response->assertRedirect(route('collections.index'));
            $this->assertSame(
                DungeonRouteCollection::MAX_COLLECTIONS,
                DungeonRouteCollection::where('user_id', $creator->id)->count(),
            );
        } finally {
            DungeonRouteCollection::where('user_id', $creator->id)->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenMoreRoutesThanTheRouteLimit_failsValidation(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRoutes = collect(range(1, DungeonRouteCollection::MAX_ROUTES + 1))
            ->map(fn(): DungeonRoute => $this->createRouteFor($creator));

        try {
            // Act
            $response = $this->actingAs($creator)->post(route('collections.savenew'), [
                'name'            => 'ZzTestTooManyRoutes',
                'published_state' => PublishedState::WORLD,
                'dungeon_routes'  => $dungeonRoutes->pluck('public_key')->all(),
            ]);

            // Assert
            $response->assertSessionHasErrors('dungeon_routes');
            $this->assertSame(
                0,
                DungeonRouteCollection::where('user_id', $creator->id)->count(),
            );
        } finally {
            DungeonRouteCollection::where('user_id', $creator->id)->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $dungeonRoutes->each(fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
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
                'dungeon_routes'  => [$foreignRoute->public_key],
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
    public function savenew_givenAFailedValidation_keepsTheSubmittedRoutesSelected(): void
    {
        // Arrange
        $creator      = $this->createCreator();
        $dungeonRoute = $this->createRouteFor($creator);
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act - no name, so validation fails and the form is shown again
            $response = $this->actingAs($creator)
                ->from(route('collections.new'))
                ->followingRedirects()
                ->post(route('collections.savenew'), [
                    'published_state' => PublishedState::WORLD,
                    'dungeon_routes'  => [$dungeonRoute->public_key],
                ]);

            // Assert
            $response->assertOk();
            $this->assertSame([$dungeonRoute->public_key], $this->listedPublicKeys((string)$response->getContent(), 'dungeon_routes'));
            $this->assertSame(0, DungeonRouteCollection::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $creator->delete();
        }
    }

    /**
     * The edit page's routes save on their own, so a details form that fails validation neither posts nor changes
     * them - the page lists the stored routes again.
     */
    #[Test]
    public function edit_givenAFailedValidationWithNoRoutesSubmitted_keepsListingTheStoredRoutes(): void
    {
        // Arrange
        $creator                = $this->createCreator();
        $dungeonRoute           = $this->createRouteFor($creator);
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $editUrl = route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]);

        try {
            // Act - no routes are posted, and the missing name fails validation
            $response = $this->actingAs($creator)
                ->from($editUrl)
                ->followingRedirects()
                ->patch($editUrl, [
                    'published_state' => PublishedState::WORLD,
                ]);

            // Assert
            $response->assertOk();
            $this->assertSame([$dungeonRoute->public_key], $this->listedPublicKeys((string)$response->getContent(), 'dungeon_routes'));
            $this->assertSame([$dungeonRoute->id], $dungeonRouteCollection->refresh()->dungeonRoutes->pluck('id')->all());
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $dungeonRoute->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenAFailedValidation_keepsTheSubmittedOrder(): void
    {
        // Arrange
        $creator = $this->createCreator();
        $alpha   = $this->createRouteFor($creator, PublishedState::WORLD, 'ZzTestAlpha');
        $bravo   = $this->createRouteFor($creator, PublishedState::WORLD, 'ZzTestBravo');
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act - no name, so validation fails and the form is shown again
            $response = $this->actingAs($creator)
                ->from(route('collections.new'))
                ->followingRedirects()
                ->post(route('collections.savenew'), [
                    'published_state' => PublishedState::WORLD,
                    'dungeon_routes'  => [$bravo->public_key, $alpha->public_key],
                ]);

            // Assert
            $response->assertOk();
            $this->assertSame(
                [$bravo->public_key, $alpha->public_key],
                $this->listedPublicKeys((string)$response->getContent(), 'dungeon_routes'),
            );
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $bravo->delete();
            $alpha->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function edit_givenRoutesStoredInNonAlphabeticalOrder_listsThemInStoredOrder(): void
    {
        // Arrange
        $creator                = $this->createCreator();
        $alpha                  = $this->createRouteFor($creator, PublishedState::WORLD, 'ZzTestAlpha');
        $bravo                  = $this->createRouteFor($creator, PublishedState::WORLD, 'ZzTestBravo');
        $charlie                = $this->createRouteFor($creator, PublishedState::WORLD, 'ZzTestCharlie');
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        foreach ([$charlie, $alpha, $bravo] as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)
                ->get(route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $this->assertSame(
                [$charlie->public_key, $alpha->public_key, $bravo->public_key],
                $this->listedPublicKeys((string)$response->getContent(), 'dungeon_routes'),
            );
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $charlie->delete();
            $bravo->delete();
            $alpha->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenMoreNewRoutesOfADungeonThanFit_failsValidationOnTheOnePastTheLimit(): void
    {
        // Arrange
        $creator                = $this->createCreator();
        $alpha                  = $this->createRouteFor($creator);
        $bravo                  = $this->createRouteFor($creator);
        $charlie                = $this->createRouteFor($creator);
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => 'ZzTestTooManyOfADungeon',
                    'published_state' => PublishedState::WORLD,
                    'dungeon_routes'  => [$alpha->public_key, $bravo->public_key, $charlie->public_key],
                ],
            );

            // Assert
            $response->assertSessionHasErrors(['dungeon_routes.2']);
            $response->assertSessionDoesntHaveErrors(['dungeon_routes.0', 'dungeon_routes.1']);
            $this->assertCount(0, $dungeonRouteCollection->refresh()->dungeonRoutes);
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $charlie->delete();
            $bravo->delete();
            $alpha->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenRoutesOfADungeonKeptFromBeforeTheLimit_savesThem(): void
    {
        // Arrange
        $creator                = $this->createCreator();
        $alpha                  = $this->createRouteFor($creator);
        $bravo                  = $this->createRouteFor($creator);
        $charlie                = $this->createRouteFor($creator);
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        foreach ([$alpha, $bravo, $charlie] as $order => $dungeonRoute) {
            $dungeonRouteCollection->dungeonRoutes()->attach($dungeonRoute->id, ['order' => $order]);
        }
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => 'ZzTestKeptPastTheLimit',
                    'published_state' => PublishedState::WORLD,
                    'dungeon_routes'  => [$charlie->public_key, $alpha->public_key, $bravo->public_key],
                ],
            );

            // Assert
            $response->assertSessionHasNoErrors();
            $this->assertSame(
                [$charlie->id, $alpha->id, $bravo->id],
                $dungeonRouteCollection->refresh()->dungeonRoutes->pluck('id')->all(),
            );
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $charlie->delete();
            $bravo->delete();
            $alpha->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenRoutesInNonAlphabeticalOrder_storesThemInSubmittedOrder(): void
    {
        // Arrange
        $creator                = $this->createCreator();
        $alpha                  = $this->createRouteFor($creator, PublishedState::WORLD, 'ZzTestAlpha');
        $bravo                  = $this->createRouteFor($creator, PublishedState::WORLD, 'ZzTestBravo');
        $charlie                = $this->createRouteFor($creator, PublishedState::WORLD, 'ZzTestCharlie', $this->otherRetailMappingVersion());
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => 'ZzTestOrderedCollection',
                    'published_state' => PublishedState::WORLD,
                    'dungeon_routes'  => [$bravo->public_key, $charlie->public_key, $alpha->public_key],
                ],
            );

            // Assert
            $response->assertSessionHasNoErrors();
            $this->assertSame(
                [$bravo->id, $charlie->id, $alpha->id],
                $dungeonRouteCollection->refresh()->dungeonRoutes->pluck('id')->all(),
            );
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $charlie->delete();
            $bravo->delete();
            $alpha->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function edit_givenAFullCollection_disablesAddingRoutes(): void
    {
        // Arrange
        $creator       = $this->createCreator();
        $dungeonRoutes = collect(range(1, DungeonRouteCollection::MAX_ROUTES + 1))
            ->map(fn(): DungeonRoute => $this->createRouteFor($creator));
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        foreach ($dungeonRoutes->take(DungeonRouteCollection::MAX_ROUTES)->values() as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)
                ->get(route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $content = (string)$response->getContent();
            // Routes are added through the route picker, which the add button opens
            $this->assertMatchesRegularExpression('/<button id="dungeon_routes_add_button"[^>]*\sdisabled/s', $content);
            $this->assertCount(DungeonRouteCollection::MAX_ROUTES, $this->listedPublicKeys($content, 'dungeon_routes'));
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $dungeonRoutes->each(fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
            $creator->delete();
        }
    }

    #[Test]
    public function create_givenAUserWithoutTeams_rendersTheVisibilityPickerWithTeamDisabled(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->get(route('collections.new'));

            // Assert
            $response->assertOk();
            $content = (string)$response->getContent();

            $this->assertMatchesRegularExpression(
                '/<select id="published_state" name="published_state" class="form-control selectpicker"/',
                $content,
            );
            $this->assertMatchesRegularExpression('/<option value="unpublished"[^>]*\sselected/', $content);
            $this->assertMatchesRegularExpression('/<option value="team"[^>]*\sdisabled/', $content);
            $this->assertDoesNotMatchRegularExpression('/<option value="world"[^>]*\sdisabled/', $content);
            $response->assertSee(e(__('view_collection.published_state_subtext.world_with_link')), false);
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function edit_givenAWorldPublishedCollection_selectsItsStateInThePicker(): void
    {
        // Arrange
        $creator                = $this->createCreator();
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)
                ->get(route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $content = (string)$response->getContent();
            $this->assertMatchesRegularExpression('/<option value="world"[^>]*\sselected/', $content);
            $this->assertDoesNotMatchRegularExpression('/<option value="unpublished"[^>]*\sselected/', $content);
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenAFailedValidation_keepsTheSubmittedVisibility(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act - no name, so validation fails and the form is shown again
            $response = $this->actingAs($creator)
                ->from(route('collections.new'))
                ->followingRedirects()
                ->post(route('collections.savenew'), [
                    'published_state' => PublishedState::WORLD_WITH_LINK,
                ]);

            // Assert
            $response->assertOk();
            $this->assertMatchesRegularExpression(
                '/<option value="world_with_link"[^>]*\sselected/',
                (string)$response->getContent(),
            );
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenValidPayload_showsTheCreatedMessageOnce(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)
                ->followingRedirects()
                ->post(route('collections.savenew'), [
                    'name'            => 'ZzTestFlashOnce',
                    'published_state' => PublishedState::UNPUBLISHED,
                ]);

            // Assert
            $response->assertOk();
            $this->assertSame(
                1,
                substr_count((string)$response->getContent(), e(__('controller.dungeonroutecollection.flash.collection_created'))),
            );
        } finally {
            DungeonRouteCollection::where('user_id', $creator->id)->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenAFailedValidation_showsEachErrorOnce(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act - no name
            $response = $this->actingAs($creator)
                ->from(route('collections.new'))
                ->followingRedirects()
                ->post(route('collections.savenew'), [
                    'published_state' => PublishedState::UNPUBLISHED,
                ]);

            // Assert
            $response->assertOk();
            $this->assertSame(1, substr_count((string)$response->getContent(), 'class="alert alert-danger'));
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function delete_givenOwnCollection_showsTheDeletedMessageOnceOnTheIndex(): void
    {
        // Arrange
        $creator                = $this->createCreator();
        $dungeonRouteCollection = DungeonRouteCollection::factory()->create(['user_id' => $creator->id]);
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)
                ->followingRedirects()
                ->delete(route('collections.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $this->assertSame(
                1,
                substr_count((string)$response->getContent(), e(__('controller.dungeonroutecollection.flash.collection_deleted'))),
            );
        } finally {
            DungeonRouteCollection::where('user_id', $creator->id)->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
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
                'category_id'     => DungeonRouteCollectionCategoryType::Beginner->id(),
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRouteCollection = DungeonRouteCollection::query()
                ->where('user_id', $creator->id)
                ->first();

            $this->assertNotNull($dungeonRouteCollection);
            $this->assertSame(
                DungeonRouteCollectionCategoryType::Beginner->id(),
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
            'dungeon_route_collection_category_id' => DungeonRouteCollectionCategoryType::Mdi->id(),
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
            'dungeon_route_collection_category_id' => DungeonRouteCollectionCategoryType::Beginner->id(),
        ]);

        try {
            // Act
            $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $response->assertSee(__(sprintf(
                'dungeonroutecollectioncategories.%s',
                DungeonRouteCollectionCategoryType::Beginner->value,
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
                    'dungeon_routes'  => [$second->public_key],
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

    #[Test]
    public function update_givenThePublishedStateWasRaisedWithALessVisibleRouteInIt_offersToRaiseTheRouteToo(): void
    {
        // Arrange
        $creator                  = $this->createCreator();
        [$route, $mappingVersion] = $this->createRouteWithEmptyMappingVersion($creator, PublishedState::UNPUBLISHED);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
        ]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $route->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => $dungeonRouteCollection->name,
                    'published_state' => PublishedState::WORLD,
                ],
            );

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertSessionHas('collection_publish_routes_confirm', [
                'count'           => 1,
                'published_state' => PublishedState::WORLD,
            ]);
            // Only offered, never raised on save itself - the user still has to confirm
            $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $route->fresh()->published_state_id);
        } finally {
            $dungeonRouteCollection->delete();
            $route->delete();
            $mappingVersion->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenThePublishedStateWasRaisedButNoRouteIsLessVisible_doesNotOfferToRaiseAnything(): void
    {
        // Arrange
        $creator                  = $this->createCreator();
        [$route, $mappingVersion] = $this->createRouteWithEmptyMappingVersion($creator, PublishedState::WORLD);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $route->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => $dungeonRouteCollection->name,
                    'published_state' => PublishedState::WORLD,
                ],
            );

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertSessionMissing('collection_publish_routes_confirm');
        } finally {
            $dungeonRouteCollection->delete();
            $route->delete();
            $mappingVersion->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenThePublishedStateWasNotRaised_doesNotOfferToRaiseAnyRoute(): void
    {
        // Arrange - lowering the collection's own state never should bring its routes along
        $creator                  = $this->createCreator();
        [$route, $mappingVersion] = $this->createRouteWithEmptyMappingVersion($creator, PublishedState::UNPUBLISHED);
        $team                     = $this->createTeamFor($creator);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $route->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => $dungeonRouteCollection->name,
                    'published_state' => PublishedState::TEAM,
                    'team_id'         => $team->id,
                ],
            );

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertSessionMissing('collection_publish_routes_confirm');
        } finally {
            $dungeonRouteCollection->delete();
            $route->delete();
            $mappingVersion->delete();
            $this->deleteTeam($team);
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenALessVisibleRouteOwnedBySomeoneElse_excludesItFromTheOffer(): void
    {
        // Arrange - a legacy collection may hold a route of another author (added before that was disallowed); the
        // owner of the collection may not publish it, so raising it is never offered
        $creator                         = $this->createCreator();
        $otherAuthor                     = User::factory()->create();
        [$foreignRoute, $mappingVersion] = $this->createRouteWithEmptyMappingVersion($otherAuthor, PublishedState::UNPUBLISHED);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
        ]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $foreignRoute->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(
                route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]),
                [
                    'name'            => $dungeonRouteCollection->name,
                    'published_state' => PublishedState::WORLD,
                ],
            );

            // Assert - the acting user may edit the collection, but does not own the foreign route in it
            $response->assertSessionHasNoErrors();
            $response->assertSessionMissing('collection_publish_routes_confirm');
        } finally {
            $dungeonRouteCollection->delete();
            $foreignRoute->delete();
            $mappingVersion->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $otherAuthor->delete();
            $creator->delete();
        }
    }

    /**
     * The route picker only offers the acting user's own routes, so an admin editing someone else's
     * collection may not add to it - but the page must still show the collection as the owner's, not
     * claim the owner has no routes at all.
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
            $this->assertFalse(
                $response->viewData('mayAddDungeonRoutes'),
                'Only the owner may add routes, so the admin gets no route picker',
            );
            $this->assertTrue(
                $response->viewData('hasOwnDungeonRoutes'),
                "The owner's routes decide this, not the acting admin's",
            );
            $this->assertStringContainsString(
                $dungeonRoute->public_key,
                (string)$response->getContent(),
                'The routes already in the collection are shown either way',
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
                    'dungeon_routes'  => [$dungeonRoute->public_key],
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
    public function view_givenACollection_titlesThePageWithItsNameAndAuthor(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for(null)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'name'               => 'ZzTestTitledCollection',
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        try {
            // Act
            $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $response->assertSee(sprintf('<title>%s', e(__('view_collection.view.title_by_author', [
                'name'   => 'ZzTestTitledCollection',
                'author' => $creator->name,
            ]))), false);
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for(null)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    /**
     * A viewer must not be told the collection holds routes they may not see - that reads as an
     * access error and leaks that unpublished routes exist.
     */
    #[Test]
    public function view_givenOnlyUnpublishedRoutesViewedByAGuest_showsTheNeutralEmptyState(): void
    {
        // Arrange
        $creator     = $this->createCreator();
        $unpublished = $this->createRouteFor($creator, PublishedState::UNPUBLISHED, 'ZzTestHiddenRoute');
        Feature::for(null)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $unpublished->id,
            'order'                       => 0,
        ]);

        try {
            // Act
            $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $response->assertSee(e(__('view_collection.view.no_routes')), false);
            $response->assertDontSee(e(__('view_collection.view.no_routes_owner')), false);
            $response->assertDontSee(route('collections.edit', $dungeonRouteCollection), false);
            $response->assertDontSee('ZzTestHiddenRoute');
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for(null)->forget(CreatorProfiles::class);
            $unpublished->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function view_givenAnEmptyCollectionViewedByItsOwner_linksToAddingRoutes(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->get(
                route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]),
            );

            // Assert
            $response->assertOk();
            $response->assertSee(e(__('view_collection.view.no_routes_owner')), false);
            $response->assertSee(route('collections.edit', $dungeonRouteCollection), false);
            $response->assertDontSee(e(__('view_collection.view.no_routes')), false);
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function edit_givenOwnCollection_namesTheCollectionInTheBreadcrumb(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id' => $creator->id,
            'name'    => 'ZzTestBreadcrumbCollection',
        ]);

        try {
            // Act
            $response = $this->actingAs($creator)->get(route('collections.edit', $dungeonRouteCollection));

            // Assert
            $response->assertOk();
            $response->assertSee(e(__('breadcrumbs.home.edit_collection', ['name' => 'ZzTestBreadcrumbCollection'])), false);
        } finally {
            $dungeonRouteCollection->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
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

    private function createRouteFor(
        User            $user,
        string          $publishedState = PublishedState::WORLD,
        ?string         $title = null,
        ?MappingVersion $mappingVersion = null,
    ): DungeonRoute {
        $mappingVersion ??= $this->retailMappingVersion();

        $attributes = [
            'author_id'          => $user->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => null,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ];

        if ($title !== null) {
            $attributes['title'] = $title;
        }

        return DungeonRoute::factory()->create($attributes);
    }

    /**
     * A route on a mapping version of its own holding no enemies at all, so hasKilledAllRequiredEnemies() always
     * passes regardless of which dungeon the factory happens to pick - needed for a route the "raise its published
     * state" offer is meant to actually be able to raise.
     *
     * @return array{0: DungeonRoute, 1: MappingVersion}
     */
    private function createRouteWithEmptyMappingVersion(User $user, string $publishedState): array
    {
        $route = DungeonRoute::factory()->create([
            'author_id'          => $user->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ]);

        $current = $route->mappingVersion;
        $now     = Carbon::now()->toDateTimeString();

        // Inserted quietly, as MappingService::copyMappingVersionToDungeon() does - the model's mutators would
        // otherwise touch fields this test does not care about
        $mappingVersion = MappingVersion::findOrFail(MappingVersion::insertGetId([
            'game_version_id'                 => $current->game_version_id,
            'dungeon_id'                      => $route->dungeon_id,
            'version'                         => $current->version + 1,
            'enemy_forces_required'           => $current->enemy_forces_required,
            'enemy_forces_required_teeming'   => $current->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $current->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $current->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $current->timer_max_seconds,
            'created_at'                      => $now,
            'updated_at'                      => $now,
        ]));

        $route->update(['mapping_version_id' => $mappingVersion->id, 'teeming' => false]);

        return [$route, $mappingVersion];
    }

    /**
     * A retail mapping version of a challenge mode dungeon, so a route on it may join a retail collection.
     */
    /**
     * A retail mapping version of another challenge mode dungeon than retailMappingVersion()'s.
     */
    private function otherRetailMappingVersion(): MappingVersion
    {
        return MappingVersion::query()
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->whereHas('dungeon', static fn($query) => $query->whereNotNull('challenge_mode_id'))
            ->where('dungeon_id', '!=', $this->retailMappingVersion()->dungeon_id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function retailMappingVersion(): MappingVersion
    {
        return MappingVersion::query()
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->whereHas('dungeon', static fn($query) => $query->whereNotNull('challenge_mode_id'))
            ->orderByDesc('id')
            ->firstOrFail();
    }

    /**
     * The ids the ordered pick list renders for a field, in list order. Its row template carries id 0.
     *
     * @return array<int, int>
     */
    /**
     * The public keys the rendered lists hold, in page order.
     *
     * @return array<int, string>
     */
    private function listedPublicKeys(string $content, string $name): array
    {
        preg_match_all(
            sprintf('/<input type="hidden" name="%s\[\]" value="([^"]*)"/', preg_quote($name, '/')),
            $content,
            $matches,
        );

        return array_values(array_filter($matches[1]));
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
