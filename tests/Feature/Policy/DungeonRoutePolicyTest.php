<?php

namespace Tests\Feature\Policy;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use App\Policies\DungeonRoutePolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Policy')]
#[Group('DungeonRoutePolicy')]
final class DungeonRoutePolicyTest extends PublicTestCase
{
    private DungeonRoutePolicy $policy;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new DungeonRoutePolicy();
    }

    #[Test]
    public function view_givenWorldPublishedRoute_returnsAllowedForGuest(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, ['published_state_id' => PublishedState::ALL[PublishedState::WORLD]]);

        try {
            // Act
            $result = $this->policy->view(null, $route);

            // Assert
            $this->assertTrue($result->allowed());
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function view_givenUnpublishedRoute_returnsDeniedForNonOwner(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonOwner = User::factory()->create();
        $route    = $this->createRoute($owner, ['published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED]]);

        try {
            // Act
            $result = $this->policy->view($nonOwner, $route);

            // Assert
            $this->assertTrue($result->denied());
        } finally {
            $route->delete();
            $owner->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function view_givenUnpublishedRoute_returnsAllowedForOwner(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, ['published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED]]);

        try {
            // Act
            $result = $this->policy->view($owner, $route);

            // Assert
            $this->assertTrue($result->allowed());
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function edit_givenOwner_returnsAllowed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->edit($owner, $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function edit_givenNonOwnerNonAdminOnNonSandboxRoute_returnsDenied(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonOwner = User::factory()->create();
        $route    = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertFalse($this->policy->edit($nonOwner, $route));
        } finally {
            $route->delete();
            $owner->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function edit_givenAdmin_returnsAllowed(): void
    {
        // Arrange
        $admin = $this->adminUser();
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->edit($admin, $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function edit_givenSandboxRouteAndGuest_returnsAllowed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, ['expires_at' => now()->addHours(2)]);

        try {
            // Act & Assert - a sandbox route (expires_at set) is editable without a user
            $this->assertTrue($this->policy->edit(null, $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function delete_givenOwner_returnsAllowed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->delete($owner, $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function delete_givenNonOwnerNonAdmin_returnsDenied(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonOwner = User::factory()->create();
        $route    = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertFalse($this->policy->delete($nonOwner, $route));
        } finally {
            $route->delete();
            $owner->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function delete_givenAdmin_returnsAllowed(): void
    {
        // Arrange
        $admin = $this->adminUser();
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->delete($admin, $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function publish_givenOwner_returnsAllowed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act
            $result = $this->policy->publish($owner, $route);

            // Assert
            $this->assertTrue($result->allowed());
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function publish_givenNonOwnerNonAdmin_returnsDenied(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonOwner = User::factory()->create();
        $route    = $this->createRoute($owner);

        try {
            // Act
            $result = $this->policy->publish($nonOwner, $route);

            // Assert
            $this->assertTrue($result->denied());
        } finally {
            $route->delete();
            $owner->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function preview_givenCorrectSecret_returnsAllowed(): void
    {
        // Arrange
        config(['keystoneguru.thumbnail.preview_secret' => 'the-secret']);
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert - a non-admin with the correct secret is allowed
            $this->assertTrue($this->policy->preview($owner, $route, 'the-secret'));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function preview_givenWrongSecretAndNonAdmin_returnsDenied(): void
    {
        // Arrange
        config(['keystoneguru.thumbnail.preview_secret' => 'the-secret']);
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertFalse($this->policy->preview($owner, $route, 'wrong-secret'));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function preview_givenWrongSecretAndAdmin_returnsAllowed(): void
    {
        // Arrange
        config(['keystoneguru.thumbnail.preview_secret' => 'the-secret']);
        $admin = $this->adminUser();
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert - an admin bypasses the secret check
            $this->assertTrue($this->policy->preview($admin, $route, 'wrong-secret'));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function rate_givenOwner_returnsDenied(): void
    {
        // Arrange - rate() reads the authenticated user, so act as the owner
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        $this->actingAs($owner);

        try {
            // Act & Assert - a user may not rate their own route
            $this->assertFalse($this->policy->rate($owner, $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function rate_givenNonOwner_returnsAllowed(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonOwner = User::factory()->create();
        $route    = $this->createRoute($owner);
        $this->actingAs($nonOwner);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->rate($nonOwner, $route));
        } finally {
            $route->delete();
            $owner->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function embed_givenSandboxRoute_returnsDenied(): void
    {
        // Arrange - a world-published but sandbox route (expires_at set)
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, [
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => now()->addHours(2),
        ]);

        try {
            // Act
            $result = $this->policy->embed($owner, $route);

            // Assert
            $this->assertTrue($result->denied());
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function embed_givenWorldPublishedNonSandboxRoute_returnsAllowed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner, ['published_state_id' => PublishedState::ALL[PublishedState::WORLD]]);

        try {
            // Act
            $result = $this->policy->embed($owner, $route);

            // Assert
            $this->assertTrue($result->allowed());
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function forceDelete_givenAdmin_returnsAllowed(): void
    {
        // Arrange
        $admin = $this->adminUser();
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->forceDelete($admin, $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function forceDelete_givenNonAdmin_returnsDenied(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertFalse($this->policy->forceDelete($owner, $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function can_givenEditAbilityAndOwner_resolvesThroughGate(): void
    {
        // Arrange - proves the policy is wired to the Gate via auto-discovery
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertTrue($owner->can('edit', $route));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * Creates a non-sandbox route owned by the given user. Override any attribute as needed.
     *
     * @param array<string, mixed> $overrides
     */
    private function createRoute(User $owner, array $overrides = []): DungeonRoute
    {
        return DungeonRoute::factory()->create(array_merge([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ], $overrides));
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
}
