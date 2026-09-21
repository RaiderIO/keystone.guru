<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Where "Add to collection…" is offered: on the route page and on My routes, to the route's owner only, and never in
 * the map editor.
 */
#[Group('Controller')]
#[Group('DungeonRouteCollection')]
final class DungeonRouteAddToCollectionEntryTest extends PublicTestCase
{
    private const string BUTTON_ID = 'id="add_to_collection_button"';
    private const string MODAL_ID  = 'id="add_to_collection_modal"';

    /** @var array<int, User> */
    private array $createdUsers = [];

    /** @var array<int, DungeonRoute> */
    private array $createdDungeonRoutes = [];

    protected function setUp(): void
    {
        parent::setUp();

        Feature::for(null)->activate(CreatorProfiles::class);

        $this->beforeApplicationDestroyed(function (): void {
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
    public function view_givenTheOwner_offersAddToCollection(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner);

        // Act
        $response = $this->actingAs($owner)->followingRedirects()->get($this->viewUrl($dungeonRoute));

        // Assert
        $response->assertOk();
        $response->assertSee(self::BUTTON_ID, false);
        $response->assertSee(self::MODAL_ID, false);
        $response->assertSee(sprintf('data-publickey="%s"', $dungeonRoute->public_key), false);
    }

    #[Test]
    public function view_givenTheOwner_offersAddToCollectionInTheLeftMenuAndNotInTheHeader(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner);

        // Act
        $content = $this->actingAs($owner)->followingRedirects()->get($this->viewUrl($dungeonRoute))->getContent();

        // Assert
        $this->assertSame(1, substr_count($content, self::BUTTON_ID));
        $this->assertGreaterThan(
            strpos($content, 'route_sidebar route_manipulation_tools left'),
            strpos($content, self::BUTTON_ID),
        );
        $this->assertStringNotContainsString('dropdown-item dungeonroute-add-to-collection', $content);
    }

    #[Test]
    public function view_givenSomeoneElse_doesNotOfferAddToCollection(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $otherUser    = $this->createUser();
        $dungeonRoute = $this->createRoute($owner);

        // Act
        $response = $this->actingAs($otherUser)->followingRedirects()->get($this->viewUrl($dungeonRoute));

        // Assert
        $response->assertOk();
        $response->assertDontSee(self::BUTTON_ID, false);
        $response->assertDontSee(self::MODAL_ID, false);
    }

    #[Test]
    public function view_givenAGuest_doesNotOfferAddToCollection(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner);

        // Act
        $response = $this->followingRedirects()->get($this->viewUrl($dungeonRoute));

        // Assert
        $response->assertOk();
        $response->assertDontSee(self::BUTTON_ID, false);
    }

    #[Test]
    public function view_givenTheOwnersSandboxRoute_doesNotOfferAddToCollection(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner);
        $dungeonRoute->update(['expires_at' => now()->addDay()]);

        // Act
        $response = $this->actingAs($owner)->followingRedirects()->get($this->viewUrl($dungeonRoute));

        // Assert
        $response->assertOk();
        $response->assertDontSee(self::BUTTON_ID, false);
        $response->assertDontSee(self::MODAL_ID, false);
    }

    #[Test]
    public function view_givenTheFeatureIsInactive_doesNotOfferAddToCollection(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner);
        Feature::for($owner)->deactivate(CreatorProfiles::class);

        // Act
        $response = $this->actingAs($owner)->followingRedirects()->get($this->viewUrl($dungeonRoute));

        // Assert
        $response->assertOk();
        $response->assertDontSee(self::BUTTON_ID, false);
    }

    #[Test]
    public function edit_givenTheOwner_doesNotOfferAddToCollectionInTheMapEditor(): void
    {
        // Arrange
        $owner        = $this->createUser();
        $dungeonRoute = $this->createRoute($owner);

        // Act
        $response = $this->actingAs($owner)->followingRedirects()->get(route('dungeonroute.edit', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertDontSee(self::BUTTON_ID, false);
        $response->assertDontSee(self::MODAL_ID, false);
    }

    #[Test]
    public function routes_givenTheFeatureIsActive_rendersTheAddToCollectionDialog(): void
    {
        // Arrange
        $owner = $this->createUser();

        // Act
        $response = $this->actingAs($owner)->get(route('profile.routes'));

        // Assert
        $response->assertOk();
        $response->assertSee(self::MODAL_ID, false);
        $response->assertSee('"showAddToCollection":true', false);
    }

    #[Test]
    public function routes_givenTheFeatureIsInactive_leavesTheAddToCollectionDialogOut(): void
    {
        // Arrange
        $owner = $this->createUser();
        Feature::for($owner)->deactivate(CreatorProfiles::class);

        // Act
        $response = $this->actingAs($owner)->get(route('profile.routes'));

        // Assert
        $response->assertOk();
        $response->assertDontSee(self::MODAL_ID, false);
        $response->assertSee('"showAddToCollection":false', false);
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);
        Feature::for($user)->activate(CreatorProfiles::class);
        $this->createdUsers[] = $user;

        return $user;
    }

    private function createRoute(User $owner): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }

    /**
     * dungeonroute.view always redirects to the default floor, so tests follow redirects.
     */
    private function viewUrl(DungeonRoute $dungeonRoute): string
    {
        return route('dungeonroute.view', [
            'dungeon'      => $dungeonRoute->dungeon,
            'dungeonroute' => $dungeonRoute,
            'title'        => $dungeonRoute->getTitleSlug(),
        ]);
    }
}
