<?php

namespace Tests\Feature\Policy;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use App\Policies\DungeonRoutePolicy;
use Illuminate\Support\Carbon;
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
        $owner            = User::factory()->create();
        $route            = $this->createRoute($owner);
        $mappingVersionId = null;

        try {
            // hasKilledAllRequiredEnemies() denies publishing when the route's mapping version has a required
            // enemy the route hasn't killed - the dungeon the factory randomly picks can carry seeded required
            // enemies (see giveRouteAnUnkilledRequiredEnemy()'s docblock), which made this happy-path test flaky.
            // Move the route onto its own guaranteed-empty mapping version so it never depends on which dungeon
            // was picked.
            $mappingVersionId = $this->giveRouteAnEmptyMappingVersion($route);

            // Act
            $result = $this->policy->publish($owner, $route);

            // Assert
            $this->assertTrue($result->allowed());
        } finally {
            $route->delete();
            $owner->delete();
            if ($mappingVersionId !== null) {
                MappingVersion::destroy($mappingVersionId);
            }
        }
    }

    #[Test]
    public function publish_givenOwnerOfRouteMissingRequiredEnemies_returnsDenied(): void
    {
        // Arrange
        $owner            = User::factory()->create();
        $route            = $this->createRoute($owner);
        $mappingVersionId = null;

        try {
            $mappingVersionId = $this->giveRouteAnUnkilledRequiredEnemy($route);

            // Act
            $result = $this->policy->publish($owner, $route->fresh(), PublishedState::WORLD);

            // Assert
            $this->assertTrue($result->denied());
        } finally {
            $this->cleanupRouteMappingVersion($route, $mappingVersionId);
            $owner->delete();
        }
    }

    #[Test]
    public function publish_givenOwnerOfRouteMissingRequiredEnemiesUnpublishing_returnsAllowed(): void
    {
        // Arrange - a route may always be made *less* visible, otherwise a route that was already published when its
        // mapping gained a required enemy would be stuck published with no way for its author to take it down.
        $owner            = User::factory()->create();
        $route            = $this->createRoute($owner);
        $mappingVersionId = null;

        try {
            $mappingVersionId = $this->giveRouteAnUnkilledRequiredEnemy($route);

            // Act
            $result = $this->policy->publish($owner, $route->fresh(), PublishedState::UNPUBLISHED);

            // Assert
            $this->assertTrue($result->allowed());
        } finally {
            $this->cleanupRouteMappingVersion($route, $mappingVersionId);
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
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

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

        try {
            // Act & Assert
            $this->assertTrue($this->policy->rate($nonOwner, $route));
        } finally {
            $route->delete();
            $owner->delete();
            $nonOwner->delete();
        }
    }

    /**
     * rate() used to call isOwnedByUser() with no argument, silently falling back to Auth::user()
     * and discarding the $user the Gate passed in. Acting as the owner while asking about a
     * different user must not deny.
     */
    #[Test]
    public function rate_givenNonOwnerWhileOwnerIsAuthenticated_returnsAllowed(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonOwner = User::factory()->create();
        $route    = $this->createRoute($owner);
        $this->actingAs($owner);

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
    public function claim_givenSandboxRoute_returnsAllowed(): void
    {
        // Arrange - a sandbox route is unowned until somebody claims it
        $user  = User::factory()->create();
        $route = $this->createRoute($user, ['expires_at' => now()->addHours(2)]);

        try {
            // Act & Assert
            $this->assertTrue($this->policy->claim($user, $route)->allowed());
        } finally {
            $route->delete();
            $user->delete();
        }
    }

    #[Test]
    public function claim_givenAlreadyOwnedRoute_returnsDenied(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert - neither a stranger nor the owner may re-claim a non-sandbox route
            $this->assertTrue($this->policy->claim($other, $route)->denied());
            $this->assertTrue($this->policy->claim($owner, $route)->denied());
        } finally {
            $route->delete();
            $other->delete();
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

    /**
     * Moves the route onto a mapping version of its own holding no enemies at all, and returns that mapping
     * version's id so the caller can clean it up. Guarantees hasKilledAllRequiredEnemies() passes regardless of
     * which dungeon the factory picked for the route - see giveRouteAnUnkilledRequiredEnemy()'s docblock for why
     * the dungeon's own current mapping version can't be relied on to be required-enemy-free.
     */
    private function giveRouteAnEmptyMappingVersion(DungeonRoute $route): int
    {
        $current = $route->mappingVersion;
        $now     = Carbon::now()->toDateTimeString();

        // Inserted quietly, as MappingService::copyMappingVersionToDungeon() does - see
        // giveRouteAnUnkilledRequiredEnemy() below for why.
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

        return $mappingVersion->id;
    }

    /**
     * Moves the route onto an empty mapping version of its own holding a single required enemy that the route does
     * not kill, and returns that mapping version's id so the caller can clean it up.
     */
    private function giveRouteAnUnkilledRequiredEnemy(DungeonRoute $route): int
    {
        $current = $route->mappingVersion;
        $now     = Carbon::now()->toDateTimeString();

        // Inserted quietly, as MappingService::copyMappingVersionToDungeon() does - MappingVersion's `created` hook
        // clones the entire previous mapping into the new version, which would drag the dungeon's own seeded
        // (possibly `required`) enemies in with it and make this test depend on the dungeon the factory picked.
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

        Enemy::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
            'npc_id'             => null,
            'teeming'            => null,
            'required'           => true,
            'lat'                => 0,
            'lng'                => 0,
        ]);

        $route->update(['mapping_version_id' => $mappingVersion->id, 'teeming' => false]);

        return $mappingVersion->id;
    }

    /**
     * Enemy and MappingVersion are SeederModels, whose delete() is silently refused - clean them up through the
     * query builder instead.
     */
    private function cleanupRouteMappingVersion(DungeonRoute $route, ?int $mappingVersionId): void
    {
        $route->delete();

        if ($mappingVersionId === null) {
            return;
        }

        Enemy::query()->where('mapping_version_id', $mappingVersionId)->delete();
        MappingVersion::query()->where('id', $mappingVersionId)->delete();
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
