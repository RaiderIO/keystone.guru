<?php

namespace Tests\Feature\Controller\Ajax;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

/**
 * The endpoints the collection edit page saves its routes through: add, remove and reorder, each immediately.
 */
#[Group('Controller')]
#[Group('DungeonRouteCollection')]
final class AjaxDungeonRouteCollectionControllerTest extends PublicTestCase
{
    use CreatesSeason;

    /** @var array<int, User> */
    private array $createdUsers = [];

    /** @var array<int, DungeonRoute> */
    private array $createdDungeonRoutes = [];

    /** @var array<int, DungeonRouteCollection> */
    private array $createdDungeonRouteCollections = [];

    protected function setUp(): void
    {
        parent::setUp();

        Feature::for(null)->activate(CreatorProfiles::class);

        $this->beforeApplicationDestroyed(function (): void {
            foreach ($this->createdDungeonRouteCollections as $dungeonRouteCollection) {
                $dungeonRouteCollection->delete();
            }

            foreach ($this->createdDungeonRoutes as $dungeonRoute) {
                $dungeonRoute->delete();
            }

            foreach ($this->createdUsers as $user) {
                Feature::for($user)->forget(CreatorProfiles::class);
                $user->delete();
            }

            Feature::for(null)->forget(CreatorProfiles::class);
        });
    }

    #[Test]
    public function storeRoutes_givenAGuest_returnsUnauthorized(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);

        // Act
        $response = $this->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertUnauthorized();
        $this->assertSame([], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenAUserWhoDoesNotOwnTheCollection_returnsForbidden(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $otherUser              = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);

        // Act
        $response = $this->actingAs($otherUser)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertForbidden();
        $this->assertSame([], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenTheFeatureIsInactive_returnsNotFound(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);
        Feature::for($owner)->deactivate(CreatorProfiles::class);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function storeRoutes_givenOwnRoutesOfTheGameVersion_appendsThemInPostedOrder(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $mappingVersions        = $this->retailMappingVersions()->take(2)->values();
        $member                 = $this->createRoute($owner, $mappingVersions->get(0));
        $alpha                  = $this->createRoute($owner, $mappingVersions->get(0));
        $bravo                  = $this->createRoute($owner, $mappingVersions->get(1));
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$member]);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$bravo, $alpha]);

        // Assert
        $response->assertOk();
        $this->assertSame([$member->id, $bravo->id, $alpha->id], $this->memberIds($dungeonRouteCollection));
        $response->assertJsonPath('dungeon_routes.0.public_key', $bravo->public_key);
        $response->assertJsonPath('dungeon_routes.0.id', $bravo->id);
        $response->assertJsonPath('dungeon_routes.0.title', $bravo->title);
        $response->assertJsonPath('dungeon_routes.0.dungeon_id', $bravo->dungeon_id);
        $response->assertJsonPath('dungeon_routes.1.public_key', $alpha->public_key);
    }

    #[Test]
    public function storeRoutes_givenAnAdminOnSomeoneElsesCollection_addsTheOwnersRoute(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $admin                  = User::findOrFail(1);
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        Feature::for($admin)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($admin)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

            // Assert
            $response->assertOk();
            $this->assertSame([$dungeonRoute->id], $this->memberIds($dungeonRouteCollection));
        } finally {
            Feature::for($admin)->forget(CreatorProfiles::class);
        }
    }

    #[Test]
    public function storeRoutes_givenARouteOfAnotherUser_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $otherUser              = $this->createUser();
        $dungeonRoute           = $this->createRoute($otherUser, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.exists')]);
        $this->assertSame([], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenASandboxRoute_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion(), null, ['expires_at' => now()->addHour()]);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('dungeon_routes.0');
    }

    #[Test]
    public function storeRoutes_givenARouteOfAnotherGameVersion_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->nonRetailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.game_version')]);
        $this->assertSame([], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenARouteWithoutAMappingVersion_failsValidation(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner, $this->retailMappingVersion());
        DungeonRoute::query()->whereKey($dungeonRoute->id)->update(['mapping_version_id' => null]);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.game_version')]);
    }

    #[Test]
    public function storeRoutes_givenARouteOfTheSeasonForASeasonSet_addsIt(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $season                 = $this->createRetailSeason();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion(), $season);
        $dungeonRouteCollection = $this->createSeasonSet($owner, $season);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertOk();
        $this->assertSame([$dungeonRoute->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenARouteOfAnotherSeasonForASeasonSet_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $season                 = $this->createRetailSeason();
        $otherSeason            = $this->createRetailSeason();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion(), $otherSeason);
        $dungeonRouteCollection = $this->createSeasonSet($owner, $season);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.season')]);
        $this->assertSame([], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenARouteAlreadyInTheCollection_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$dungeonRoute]);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.already_in')]);
        $this->assertSame([$dungeonRoute->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenTheSameRouteTwice_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$dungeonRoute, $dungeonRoute]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.distinct')]);
    }

    #[Test]
    public function storeRoutes_givenNoRoutes_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRouteCollection = $this->createFreeFormCollection($owner);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), []);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes' => __('validation.custom.collection_dungeon_routes.required')]);
    }

    #[Test]
    public function storeRoutes_givenMoreRoutesThanFit_failsValidationAndAddsNone(): void
    {
        // Arrange - one place left, two routes posted
        $owner          = $this->createUser();
        $mappingVersion = $this->retailMappingVersion();
        $members        = collect(range(1, DungeonRouteCollection::MAX_ROUTES - 1))
            ->map(fn(): DungeonRoute => $this->createRoute($owner, $mappingVersion))
            ->all();
        $alpha                  = $this->createRoute($owner, $mappingVersion);
        $bravo                  = $this->createRoute($owner, $mappingVersion);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, $members);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$alpha, $bravo]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes' => __('validation.custom.collection_dungeon_routes.max', ['max' => DungeonRouteCollection::MAX_ROUTES])]);
        $this->assertCount(DungeonRouteCollection::MAX_ROUTES - 1, $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenExactlyTheLastPlace_addsTheRoute(): void
    {
        // Arrange
        $owner          = $this->createUser();
        $mappingVersion = $this->retailMappingVersion();
        $members        = collect(range(1, DungeonRouteCollection::MAX_ROUTES - 1))
            ->map(fn(): DungeonRoute => $this->createRoute($owner, $mappingVersion))
            ->all();
        $last                   = $this->createRoute($owner, $mappingVersion);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, $members);

        // Act
        $response = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$last]);

        // Assert
        $response->assertOk();
        $this->assertCount(DungeonRouteCollection::MAX_ROUTES, $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function deleteRoutes_givenAGuest_returnsUnauthorized(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$dungeonRoute]);

        // Act
        $response = $this->ajax('deleteJson', $this->deleteUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertUnauthorized();
        $this->assertSame([$dungeonRoute->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function deleteRoutes_givenAUserWhoDoesNotOwnTheCollection_returnsForbidden(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $otherUser              = $this->createUser();
        $dungeonRoute           = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$dungeonRoute]);

        // Act
        $response = $this->actingAs($otherUser)->ajax('deleteJson', $this->deleteUrl($dungeonRouteCollection), [$dungeonRoute]);

        // Assert
        $response->assertForbidden();
        $this->assertSame([$dungeonRoute->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function deleteRoutes_givenAMember_removesItAndKeepsTheOrderOfTheOthers(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $mappingVersion         = $this->retailMappingVersion();
        $alpha                  = $this->createRoute($owner, $mappingVersion);
        $bravo                  = $this->createRoute($owner, $mappingVersion);
        $charlie                = $this->createRoute($owner, $mappingVersion);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$charlie, $alpha, $bravo]);

        // Act
        $response = $this->actingAs($owner)->ajax('deleteJson', $this->deleteUrl($dungeonRouteCollection), [$alpha]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('dungeon_routes', [$alpha->public_key]);
        $this->assertSame([$charlie->id, $bravo->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function deleteRoutes_givenAMemberOfAnotherGameVersion_removesIt(): void
    {
        // Arrange - a legacy mixed collection can only lose its foreign routes
        $owner                  = $this->createUser();
        $foreign                = $this->createRoute($owner, $this->nonRetailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$foreign]);

        // Act
        $response = $this->actingAs($owner)->ajax('deleteJson', $this->deleteUrl($dungeonRouteCollection), [$foreign]);

        // Assert
        $response->assertOk();
        $this->assertSame([], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function deleteRoutes_givenARouteNotInTheCollection_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $member                 = $this->createRoute($owner, $this->retailMappingVersion());
        $notAMember             = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$member]);

        // Act
        $response = $this->actingAs($owner)->ajax('deleteJson', $this->deleteUrl($dungeonRouteCollection), [$notAMember]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.not_in')]);
        $this->assertSame([$member->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function deleteRoutes_givenTheRoutesJustAdded_undoesTheAdd(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $mappingVersion         = $this->retailMappingVersion();
        $member                 = $this->createRoute($owner, $mappingVersion);
        $alpha                  = $this->createRoute($owner, $mappingVersion);
        $bravo                  = $this->createRoute($owner, $mappingVersion);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$member]);
        $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$alpha, $bravo])->assertOk();

        // Act
        $response = $this->actingAs($owner)->ajax('deleteJson', $this->deleteUrl($dungeonRouteCollection), [$alpha, $bravo]);

        // Assert
        $response->assertOk();
        $this->assertSame([$member->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function storeRoutes_givenARouteJustRemoved_addsItBackSoItsPlaceCanBeRestored(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $mappingVersion         = $this->retailMappingVersion();
        $alpha                  = $this->createRoute($owner, $mappingVersion);
        $bravo                  = $this->createRoute($owner, $mappingVersion);
        $charlie                = $this->createRoute($owner, $mappingVersion);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$alpha, $bravo, $charlie]);
        $this->actingAs($owner)->ajax('deleteJson', $this->deleteUrl($dungeonRouteCollection), [$alpha])->assertOk();

        // Act - undo re-adds the route, which lands at the end, then restores the previous order
        $storeResponse = $this->actingAs($owner)->ajax('postJson', $this->storeUrl($dungeonRouteCollection), [$alpha]);
        $orderResponse = $this->actingAs($owner)->ajax('putJson', $this->orderUrl($dungeonRouteCollection), [$alpha, $bravo, $charlie]);

        // Assert
        $storeResponse->assertOk();
        $orderResponse->assertOk();
        $this->assertSame([$alpha->id, $bravo->id, $charlie->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function updateRoutesOrder_givenAGuest_returnsUnauthorized(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $alpha                  = $this->createRoute($owner, $this->retailMappingVersion());
        $bravo                  = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$alpha, $bravo]);

        // Act
        $response = $this->ajax('putJson', $this->orderUrl($dungeonRouteCollection), [$bravo, $alpha]);

        // Assert
        $response->assertUnauthorized();
        $this->assertSame([$alpha->id, $bravo->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function updateRoutesOrder_givenAUserWhoDoesNotOwnTheCollection_returnsForbidden(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $otherUser              = $this->createUser();
        $alpha                  = $this->createRoute($owner, $this->retailMappingVersion());
        $bravo                  = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$alpha, $bravo]);

        // Act
        $response = $this->actingAs($otherUser)->ajax('putJson', $this->orderUrl($dungeonRouteCollection), [$bravo, $alpha]);

        // Assert
        $response->assertForbidden();
        $this->assertSame([$alpha->id, $bravo->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function updateRoutesOrder_givenEveryMemberInANewOrder_storesIt(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $mappingVersion         = $this->retailMappingVersion();
        $alpha                  = $this->createRoute($owner, $mappingVersion);
        $bravo                  = $this->createRoute($owner, $mappingVersion);
        $charlie                = $this->createRoute($owner, $mappingVersion);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$alpha, $bravo, $charlie]);

        // Act
        $response = $this->actingAs($owner)->ajax('putJson', $this->orderUrl($dungeonRouteCollection), [$charlie, $alpha, $bravo]);

        // Assert
        $response->assertOk();
        $this->assertSame([$charlie->id, $alpha->id, $bravo->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function updateRoutesOrder_givenAnOwnRouteThatIsNotInTheCollection_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $mappingVersion         = $this->retailMappingVersion();
        $alpha                  = $this->createRoute($owner, $mappingVersion);
        $bravo                  = $this->createRoute($owner, $mappingVersion);
        $notAMember             = $this->createRoute($owner, $mappingVersion);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$alpha, $bravo]);

        // Act
        $response = $this->actingAs($owner)->ajax('putJson', $this->orderUrl($dungeonRouteCollection), [$bravo, $alpha, $notAMember]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.2' => __('validation.custom.collection_dungeon_routes.not_in')]);
        $this->assertSame([$alpha->id, $bravo->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function updateRoutesOrder_givenARouteOfAnotherUser_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $otherUser              = $this->createUser();
        $alpha                  = $this->createRoute($owner, $this->retailMappingVersion());
        $foreign                = $this->createRoute($otherUser, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$alpha]);

        // Act
        $response = $this->actingAs($owner)->ajax('putJson', $this->orderUrl($dungeonRouteCollection), [$foreign, $alpha]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.exists')]);
    }

    #[Test]
    public function updateRoutesOrder_givenAMissingMember_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $mappingVersion         = $this->retailMappingVersion();
        $alpha                  = $this->createRoute($owner, $mappingVersion);
        $bravo                  = $this->createRoute($owner, $mappingVersion);
        $charlie                = $this->createRoute($owner, $mappingVersion);
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$alpha, $bravo, $charlie]);

        // Act
        $response = $this->actingAs($owner)->ajax('putJson', $this->orderUrl($dungeonRouteCollection), [$charlie, $alpha]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes' => __('validation.custom.collection_dungeon_routes.missing')]);
        $this->assertSame([$alpha->id, $bravo->id, $charlie->id], $this->memberIds($dungeonRouteCollection));
    }

    #[Test]
    public function updateRoutesOrder_givenADuplicate_failsValidation(): void
    {
        // Arrange
        $owner                  = $this->createUser();
        $alpha                  = $this->createRoute($owner, $this->retailMappingVersion());
        $bravo                  = $this->createRoute($owner, $this->retailMappingVersion());
        $dungeonRouteCollection = $this->createFreeFormCollection($owner, [$alpha, $bravo]);

        // Act
        $response = $this->actingAs($owner)->ajax('putJson', $this->orderUrl($dungeonRouteCollection), [$bravo, $alpha, $alpha]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeon_routes.1' => __('validation.custom.collection_dungeon_routes.distinct')]);
    }

    /**
     * @param  array<int, DungeonRoute>          $dungeonRoutes
     * @return TestResponse<JsonResponse>
     */
    private function ajax(string $method, string $url, array $dungeonRoutes): TestResponse
    {
        return $this->withHeader('X-Requested-With', 'XMLHttpRequest')->{$method}($url, [
            'dungeon_routes' => array_map(static fn(DungeonRoute $dungeonRoute): string => $dungeonRoute->public_key, $dungeonRoutes),
        ]);
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);
        Feature::for($user)->activate(CreatorProfiles::class);
        $this->createdUsers[] = $user;

        return $user;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createRoute(User $author, MappingVersion $mappingVersion, ?Season $season = null, array $attributes = []): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create(array_merge([
            'author_id'          => $author->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $season?->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ], $attributes));
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }

    /**
     * @param array<int, DungeonRoute> $dungeonRoutes In collection order.
     */
    private function createFreeFormCollection(User $owner, array $dungeonRoutes = []): DungeonRouteCollection
    {
        return $this->createCollection(
            DungeonRouteCollection::factory()->freeForm($this->retail())->create(['user_id' => $owner->id]),
            $dungeonRoutes,
        );
    }

    private function createSeasonSet(User $owner, Season $season): DungeonRouteCollection
    {
        return $this->createCollection(
            DungeonRouteCollection::factory()->seasonSet($season)->create(['user_id' => $owner->id]),
            [],
        );
    }

    /**
     * @param array<int, DungeonRoute> $dungeonRoutes
     */
    private function createCollection(DungeonRouteCollection $dungeonRouteCollection, array $dungeonRoutes): DungeonRouteCollection
    {
        $this->createdDungeonRouteCollections[] = $dungeonRouteCollection;

        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }

        return $dungeonRouteCollection;
    }

    /**
     * @return array<int, int> Route ids in collection order.
     */
    private function memberIds(DungeonRouteCollection $dungeonRouteCollection): array
    {
        return DungeonRouteCollectionRoute::query()
            ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
            ->orderBy('order')
            ->pluck('dungeon_route_id')
            ->all();
    }

    private function retail(): GameVersion
    {
        return GameVersion::query()->findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]);
    }

    private function createRetailSeason(): Season
    {
        return $this->createSeason(['expansion_id' => $this->retail()->expansion_id], [$this->retailMappingVersion()->dungeon_id]);
    }

    private function retailMappingVersion(): MappingVersion
    {
        return $this->retailMappingVersions()->firstOrFail();
    }

    /**
     * Retail mapping versions of distinct challenge mode dungeons, newest first.
     *
     * @return Collection<int, MappingVersion>
     */
    private function retailMappingVersions(): Collection
    {
        return MappingVersion::query()
            ->where('game_version_id', $this->retail()->id)
            ->whereHas('dungeon', static fn(Builder $query) => $query->whereNotNull('challenge_mode_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('dungeon_id')
            ->values();
    }

    private function nonRetailMappingVersion(): MappingVersion
    {
        return MappingVersion::query()
            ->where('game_version_id', '!=', $this->retail()->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function storeUrl(DungeonRouteCollection $dungeonRouteCollection): string
    {
        return route('ajax.collection.routes.store', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }

    private function deleteUrl(DungeonRouteCollection $dungeonRouteCollection): string
    {
        return route('ajax.collection.routes.delete', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }

    private function orderUrl(DungeonRouteCollection $dungeonRouteCollection): string
    {
        return route('ajax.collection.routes.order', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }
}
