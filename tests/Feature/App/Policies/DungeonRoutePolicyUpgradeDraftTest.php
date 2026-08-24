<?php

namespace Tests\Feature\App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use App\Policies\DungeonRoutePolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Policy')]
class DungeonRoutePolicyUpgradeDraftTest extends PublicTestCase
{
    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

        return $user;
    }

    #[Test]
    public function publish_givenUpgradeDraft_returnsDeny(): void
    {
        $owner    = null;
        $original = null;
        $draft    = null;

        try {
            // Arrange
            $owner    = $this->createUser();
            $original = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);
            $draft    = DungeonRoute::factory()->create([
                'author_id'                   => $owner->id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
            ]);

            // Act
            $response = new DungeonRoutePolicy()->publish($owner, $draft, PublishedState::WORLD);

            // Assert
            $this->assertTrue($response->denied(), 'An upgrade draft may never be published on its own');
        } finally {
            $draft?->delete();
            $original?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function publish_givenNormalRoute_returnsAllow(): void
    {
        $owner = null;
        $route = null;

        try {
            // Arrange
            $owner = $this->createUser();
            $route = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);

            // Act
            $response = new DungeonRoutePolicy()->publish($owner, $route, PublishedState::UNPUBLISHED);

            // Assert
            $this->assertTrue($response->allowed(), 'A normal route is still publishable by its author');
        } finally {
            $route?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function applyUpgrade_givenDraftAndAuthor_returnsAllow(): void
    {
        $owner    = null;
        $original = null;
        $draft    = null;

        try {
            // Arrange
            $owner    = $this->createUser();
            $original = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);
            $draft    = DungeonRoute::factory()->create([
                'author_id'                   => $owner->id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
            ]);

            // Act
            $response = new DungeonRoutePolicy()->applyUpgrade($owner, $draft);

            // Assert
            $this->assertTrue($response->allowed());
        } finally {
            $draft?->delete();
            $original?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function applyUpgrade_givenDraftAndUnrelatedUser_returnsDeny(): void
    {
        $owner      = null;
        $unrelated  = null;
        $original   = null;
        $draft      = null;

        try {
            // Arrange
            $owner     = $this->createUser();
            $unrelated = $this->createUser();
            $original  = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);
            $draft     = DungeonRoute::factory()->create([
                'author_id'                   => $owner->id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
            ]);

            // Act
            $response = new DungeonRoutePolicy()->applyUpgrade($unrelated, $draft);

            // Assert
            $this->assertTrue($response->denied());
        } finally {
            $draft?->delete();
            $original?->delete();
            $unrelated?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function applyUpgrade_givenNonDraft_returnsDeny(): void
    {
        $owner = null;
        $route = null;

        try {
            // Arrange
            $owner = $this->createUser();
            $route = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);

            // Act
            $response = new DungeonRoutePolicy()->applyUpgrade($owner, $route);

            // Assert
            $this->assertTrue($response->denied());
        } finally {
            $route?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function applyUpgrade_givenDraftWithDeletedOriginal_returnsDeny(): void
    {
        $owner   = null;
        $draftId = null;

        try {
            // Arrange - a draft whose upgrade_of_dungeon_route_id points at a route that no longer exists
            $owner    = $this->createUser();
            $original = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);
            $draft    = DungeonRoute::factory()->create([
                'author_id'                   => $owner->id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
            ]);
            $draftId = $draft->id;

            // Delete only the original row, so the dangling link the policy guards against is reproduced
            DungeonRoute::query()->whereKey($original->id)->delete();

            // Act
            $response = new DungeonRoutePolicy()->applyUpgrade($owner, $draft->refresh());

            // Assert
            $this->assertTrue($response->denied());
        } finally {
            if ($draftId !== null) {
                DungeonRoute::find($draftId)?->delete();
            }
            $owner?->delete();
        }
    }

    #[Test]
    public function discardUpgrade_givenDraftAndAuthor_returnsAllow(): void
    {
        $owner    = null;
        $original = null;
        $draft    = null;

        try {
            // Arrange
            $owner    = $this->createUser();
            $original = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);
            $draft    = DungeonRoute::factory()->create([
                'author_id'                   => $owner->id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
            ]);

            // Act
            $response = new DungeonRoutePolicy()->discardUpgrade($owner, $draft);

            // Assert
            $this->assertTrue($response->allowed());
        } finally {
            $draft?->delete();
            $original?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function discardUpgrade_givenNonDraft_returnsDeny(): void
    {
        $owner = null;
        $route = null;

        try {
            // Arrange
            $owner = $this->createUser();
            $route = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);

            // Act
            $response = new DungeonRoutePolicy()->discardUpgrade($owner, $route);

            // Assert
            $this->assertTrue($response->denied());
        } finally {
            $route?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function getAvailablePublishedStates_givenUpgradeDraft_returnsOnlyUnpublished(): void
    {
        $owner    = null;
        $original = null;
        $draft    = null;

        try {
            // Arrange
            $owner    = $this->createUser();
            $original = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);
            $draft    = DungeonRoute::factory()->create([
                'author_id'                   => $owner->id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
            ]);

            // Act
            $states = PublishedState::getAvailablePublishedStates($draft, $owner);

            // Assert
            $this->assertSame([PublishedState::UNPUBLISHED], $states->toArray());
        } finally {
            $draft?->delete();
            $original?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function save_givenUpgradeDraftWithPublishedStateInInput_keepsDraftUnpublished(): void
    {
        $owner    = null;
        $original = null;
        $draft    = null;

        try {
            // Arrange
            $owner    = $this->createUser();
            $original = DungeonRoute::factory()->create(['author_id' => $owner->id, 'expires_at' => null]);
            $draft    = DungeonRoute::factory()->create([
                'author_id'                   => $owner->id,
                'dungeon_id'                  => $original->dungeon_id,
                'mapping_version_id'          => $original->mapping_version_id,
                'upgrade_of_dungeon_route_id' => $original->id,
                'expires_at'                  => null,
            ]);

            // Act - the saving hook is the silent backstop, so even a forceFill() cannot publish a draft
            $draft->forceFill(['published_state_id' => PublishedState::ALL[PublishedState::WORLD]])->save();

            // Assert
            $this->assertSame(
                PublishedState::ALL[PublishedState::UNPUBLISHED],
                DungeonRoute::findOrFail($draft->id)->published_state_id,
            );
        } finally {
            $draft?->delete();
            $original?->delete();
            $owner?->delete();
        }
    }
}
