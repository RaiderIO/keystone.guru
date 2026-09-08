<?php

namespace Tests\Feature\Policy;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The 'create-tag' ability is invoked as
 * Gate::authorize('create-tag', [$tagCategory, $model, $context]).
 * The Gate resolves the policy from the FIRST array element, so TagCategoryPolicy handles it and
 * the byte-identical TagPolicy::createTag was unreachable. These tests go through the Gate rather
 * than instantiating the policy, so they would catch the resolution silently moving elsewhere.
 */
#[Group('Policy')]
#[Group('TagCategoryPolicy')]
final class TagCategoryPolicyTest extends PublicTestCase
{
    #[Test]
    public function createTag_givenRouteOwner_returnsAllowed(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertTrue($owner->can('create-tag', [
                $this->personalTagCategory(),
                $route,
                $owner,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function createTag_givenNonOwnerOfUnpublishedRoute_returnsDenied(): void
    {
        // Arrange
        $owner    = User::factory()->create();
        $nonOwner = User::factory()->create();
        $route    = $this->createRoute($owner, [
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            // Act & Assert
            $this->assertFalse($nonOwner->can('create-tag', [
                $this->personalTagCategory(),
                $route,
                $nonOwner,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function createTag_givenUnknownTagCategory_returnsDenied(): void
    {
        // Arrange - the policy only recognises the two dungeon route categories
        $owner         = User::factory()->create();
        $route         = $this->createRoute($owner);
        $unknown       = new TagCategory();
        $unknown->id   = 0;
        $unknown->name = 'something_else';

        try {
            // Act & Assert
            $this->assertFalse($owner->can('create-tag', [$unknown, $route, $owner]));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function createTag_givenAnotherUsersContext_returnsDenied(): void
    {
        // Arrange - the route is the owner's to edit, but the tag would land in someone else's context
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertFalse($owner->can('create-tag', [
                $this->personalTagCategory(),
                $route,
                $other,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
            $other->delete();
        }
    }

    #[Test]
    public function createTag_givenTeamContextOfATeamTheUserIsAMemberOf_returnsAllowed(): void
    {
        // Arrange
        $member = User::factory()->create();
        $team   = $this->createTeam();
        $team->addMember($member, TeamUser::ROLE_MEMBER);
        $route = $this->createRoute($member);

        try {
            // Act & Assert
            $this->assertTrue($member->can('create-tag', [
                $this->teamTagCategory(),
                $route,
                $team,
            ]));
        } finally {
            $route->delete();
            TeamUser::where('team_id', $team->id)->delete();
            Team::where('id', $team->id)->delete();
            $member->delete();
        }
    }

    #[Test]
    public function createTag_givenTeamContextOfATeamTheUserIsNotAMemberOf_returnsDenied(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $team  = $this->createTeam();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertFalse($owner->can('create-tag', [
                $this->teamTagCategory(),
                $route,
                $team,
            ]));
        } finally {
            $route->delete();
            Team::where('id', $team->id)->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function createTag_givenTeamCategoryInAUserContext_returnsDenied(): void
    {
        // Arrange - the context is the caller's own, but a team-category tag stored against a user
        // is read back by DungeonRoute::tagsteam(), which selects on the category alone
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);

        try {
            // Act & Assert
            $this->assertFalse($owner->can('create-tag', [
                $this->teamTagCategory(),
                $route,
                $owner,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function createTag_givenPersonalCategoryInATeamContext_returnsDenied(): void
    {
        // Arrange - a team the caller really is a member of, paired with the wrong category
        $member = User::factory()->create();
        $team   = $this->createTeam();
        $team->addMember($member, TeamUser::ROLE_MEMBER);
        $route = $this->createRoute($member);

        try {
            // Act & Assert
            $this->assertFalse($member->can('create-tag', [
                $this->personalTagCategory(),
                $route,
                $team,
            ]));
        } finally {
            $route->delete();
            TeamUser::where('team_id', $team->id)->delete();
            Team::where('id', $team->id)->delete();
            $member->delete();
        }
    }

    private function personalTagCategory(): TagCategory
    {
        return TagCategory::where('name', TagCategory::DUNGEON_ROUTE_PERSONAL)->firstOrFail();
    }

    private function teamTagCategory(): TagCategory
    {
        return TagCategory::where('name', TagCategory::DUNGEON_ROUTE_TEAM)->firstOrFail();
    }

    private function createTeam(): Team
    {
        return Team::create([
            'public_key'   => fake()->unique()->uuid(),
            'name'         => sprintf('test-team-%s', fake()->uuid()),
            'description'  => 'Created by TagCategoryPolicyTest',
            'invite_code'  => fake()->unique()->uuid(),
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
    }

    /**
     * Creates a non-sandbox route owned by the given user. Sandbox routes are editable by anyone,
     * which would make an authorization assertion meaningless.
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
}
