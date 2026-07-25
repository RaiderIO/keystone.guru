<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use App\Models\UserPinnedDungeonRoute;
use App\Models\UserSocialLink;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class ProfileCreatorProfileTest extends PublicTestCase
{
    #[Test]
    public function view_givenFeatureInactive_doesNotRenderThePodium(): void
    {
        // Arrange
        $creator = User::factory()->create(['bio' => 'I make routes']);
        Feature::for($creator)->deactivate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->get(route('profile.view', ['user' => $creator]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('creatorProfileActive', false);
            $response->assertDontSee('I make routes');
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    #[Test]
    public function view_givenFeatureActive_rendersBioAndSocialLinks(): void
    {
        // Arrange
        $creator = User::factory()->create(['bio' => 'I make pug friendly routes']);
        Feature::for($creator)->activate(CreatorProfiles::class);

        $socialLink           = new UserSocialLink();
        $socialLink->user_id  = $creator->id;
        $socialLink->platform = UserSocialLink::PLATFORM_TWITCH;
        $socialLink->url      = 'https://twitch.tv/someone';
        $socialLink->save();

        try {
            // Act
            $response = $this->actingAs($creator)->get(route('profile.view', ['user' => $creator]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('creatorProfileActive', true);
            $response->assertSee('I make pug friendly routes');
            $response->assertSee('https://twitch.tv/someone', false);
        } finally {
            $socialLink->delete();
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    /**
     * Pinning records intent only - the viewer's own visibility rules still decide what is shown.
     * If this regresses, pinning becomes a way to publish a private route by accident.
     */
    #[Test]
    public function view_givenAPinnedUnpublishedRoute_hidesItFromOtherViewers(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $viewer  = User::factory()->create();

        $unpublishedRoute = DungeonRoute::factory()->create([
            'author_id'          => $creator->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'title'              => 'Secret unpublished route',
        ]);

        $pin                   = new UserPinnedDungeonRoute();
        $pin->user_id          = $creator->id;
        $pin->dungeon_route_id = $unpublishedRoute->id;
        $pin->order            = 0;
        $pin->save();

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('profile.view', ['user' => $creator]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('pinnedDungeonRoutes', static fn($routes): bool => $routes->isEmpty());
            $response->assertDontSee('Secret unpublished route');
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $pin->delete();
            $unpublishedRoute->delete();
            $viewer->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function view_givenAPinnedPublishedRoute_showsItToOtherViewers(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $viewer  = User::factory()->create();

        $publishedRoute = DungeonRoute::factory()->create([
            'author_id'          => $creator->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $pin                   = new UserPinnedDungeonRoute();
        $pin->user_id          = $creator->id;
        $pin->dungeon_route_id = $publishedRoute->id;
        $pin->order            = 0;
        $pin->save();

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('profile.view', ['user' => $creator]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('pinnedDungeonRoutes', static fn($routes): bool => $routes->count() === 1);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $pin->delete();
            $publishedRoute->delete();
            $viewer->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function updateCreatorProfile_givenValidPayload_persistsBioSocialsAndPins(): void
    {
        // Arrange
        $creator = $this->createCreator();
        $route   = DungeonRoute::factory()->create([
            'author_id'  => $creator->id,
            'expires_at' => null,
        ]);

        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(route('profile.creator.update'), [
                'bio'                         => 'Routes for everyone',
                'hide_from_creator_directory' => 1,
                'social_links'                => [
                    UserSocialLink::PLATFORM_TWITCH  => 'https://twitch.tv/someone',
                    UserSocialLink::PLATFORM_YOUTUBE => '',
                ],
                'pinned_dungeon_routes' => [$route->id],
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('profile.edit'));

            $creator->refresh();
            $this->assertSame('Routes for everyone', $creator->bio);
            $this->assertTrue($creator->hide_from_creator_directory);

            // The blank YouTube field must not be stored as an empty link
            $this->assertSame(1, $creator->socialLinks()->count());
            $this->assertSame('https://twitch.tv/someone', $creator->socialLinks()->first()->url);

            $this->assertSame(1, $creator->pinnedDungeonRoutes()->count());
            $this->assertSame($route->id, $creator->pinnedDungeonRoutes()->first()->dungeon_route_id);
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            UserSocialLink::where('user_id', $creator->id)->delete();
            UserPinnedDungeonRoute::where('user_id', $creator->id)->delete();
            $route->delete();
            $creator->delete();
        }
    }

    /**
     * Without the author_id constraint on the exists rule, any user could pin - and thereby
     * surface on their own profile - a route belonging to somebody else.
     */
    #[Test]
    public function updateCreatorProfile_givenARouteOwnedByAnotherUser_failsValidation(): void
    {
        // Arrange
        $creator      = $this->createCreator();
        $someoneElse  = User::factory()->create();
        $foreignRoute = DungeonRoute::factory()->create([
            'author_id'  => $someoneElse->id,
            'expires_at' => null,
        ]);

        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(route('profile.creator.update'), [
                'pinned_dungeon_routes' => [$foreignRoute->id],
            ]);

            // Assert
            $response->assertSessionHasErrors('pinned_dungeon_routes.0');
            $this->assertSame(0, UserPinnedDungeonRoute::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            UserPinnedDungeonRoute::where('user_id', $creator->id)->delete();
            $foreignRoute->delete();
            $someoneElse->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function updateCreatorProfile_givenAnInvalidSocialUrl_failsValidation(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(route('profile.creator.update'), [
                'social_links' => [
                    UserSocialLink::PLATFORM_TWITCH => 'https://evil.example.com/someone',
                ],
            ]);

            // Assert
            $response->assertSessionHasErrors('social_links.twitch');
            $this->assertSame(0, UserSocialLink::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            UserSocialLink::where('user_id', $creator->id)->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function updateCreatorProfile_givenMoreThanTheMaximumPins_failsValidation(): void
    {
        // Arrange
        $creator = $this->createCreator();
        $routes  = collect();

        for ($i = 0; $i <= UserPinnedDungeonRoute::MAX_PINNED_ROUTES; $i++) {
            $routes->push(DungeonRoute::factory()->create([
                'author_id'  => $creator->id,
                'expires_at' => null,
            ]));
        }

        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(route('profile.creator.update'), [
                'pinned_dungeon_routes' => $routes->pluck('id')->all(),
            ]);

            // Assert
            $response->assertSessionHasErrors('pinned_dungeon_routes');
            $this->assertSame(0, UserPinnedDungeonRoute::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            UserPinnedDungeonRoute::where('user_id', $creator->id)->delete();
            foreach ($routes as $route) {
                $route->delete();
            }
            $creator->delete();
        }
    }

    /**
     * user_pinned_dungeon_routes is unique on (user_id, dungeon_route_id), so a duplicate must be
     * rejected by validation rather than blowing up on the constraint mid-transaction.
     */
    #[Test]
    public function updateCreatorProfile_givenTheSameRoutePinnedTwice_failsValidation(): void
    {
        // Arrange
        $creator = $this->createCreator();
        $route   = DungeonRoute::factory()->create([
            'author_id'  => $creator->id,
            'expires_at' => null,
        ]);

        Feature::for($creator)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(route('profile.creator.update'), [
                'pinned_dungeon_routes' => [$route->id, $route->id],
            ]);

            // Assert
            $response->assertSessionHasErrors('pinned_dungeon_routes.0');
            $this->assertSame(0, UserPinnedDungeonRoute::where('user_id', $creator->id)->count());
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            UserPinnedDungeonRoute::where('user_id', $creator->id)->delete();
            $route->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function updateCreatorProfile_givenFeatureInactive_returnsNotFound(): void
    {
        // Arrange
        $creator = $this->createCreator();
        Feature::for($creator)->deactivate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($creator)->patch(route('profile.creator.update'), [
                'bio' => 'Should not be saved',
            ]);

            // Assert
            $response->assertNotFound();
            $creator->refresh();
            $this->assertNull($creator->bio);
        } finally {
            Feature::for($creator)->forget(CreatorProfiles::class);
            $creator->delete();
        }
    }

    /**
     * The profile routes sit behind the role:user|admin middleware, and a factory user carries no
     * roles at all - without this it is a 403 rather than the behaviour under test.
     */
    private function createCreator(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }
}
