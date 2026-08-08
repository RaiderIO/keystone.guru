<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Tag')]
final class AjaxTagControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultHeaders = [
            'X-Requested-With' => 'XMLHttpRequest',
        ];
    }

    #[Test]
    public function delete_givenOwnTag_deletesIt(): void
    {
        $owner = null;
        $tag   = null;

        try {
            // Arrange
            $owner = User::factory()->create();
            $tag   = $this->createUserTagFor($owner);

            // Act
            $response = $this->actingAs($owner)->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertNoContent();
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp(tag: $tag, users: [$owner]);
        }
    }

    #[Test]
    public function delete_givenAnotherUsersTag_returnsForbidden(): void
    {
        $owner = null;
        $other = null;
        $tag   = null;

        try {
            // Arrange
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $tag   = $this->createUserTagFor($owner);

            // Act
            $response = $this->actingAs($other)->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseHas('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp(tag: $tag, users: [$other, $owner]);
        }
    }

    #[Test]
    public function delete_givenGuest_returnsForbidden(): void
    {
        // The ajax route group carries no auth middleware on purpose - sandbox routes must work for
        // guests - so the policy is the only thing standing between a guest and someone else's tag.
        $owner = null;
        $tag   = null;

        try {
            // Arrange
            $owner = User::factory()->create();
            $tag   = $this->createUserTagFor($owner);

            // Act
            $response = $this->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseHas('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp(tag: $tag, users: [$owner]);
        }
    }

    #[Test]
    public function delete_givenTeamTagAsTeamMember_deletesIt(): void
    {
        $member = null;
        $team   = null;
        $route  = null;
        $tag    = null;

        try {
            // Arrange
            $member = User::factory()->create();
            $team   = $this->createTeam();
            $team->addMember($member, TeamUser::ROLE_MEMBER);
            $route = DungeonRoute::factory()->create(['author_id' => $member->id]);
            $tag   = $this->createTeamTag($team, $route);

            // Act
            $response = $this->actingAs($member)->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertNoContent();
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp(tag: $tag, route: $route, team: $team, users: [$member]);
        }
    }

    #[Test]
    public function delete_givenTeamTagOnOwnRouteWhileNotATeamMember_deletesIt(): void
    {
        // Team::removeMember() now drops a leaving member's team tags atomically as part of the
        // same transaction that unassigns their routes (see
        // TeamTest::removeMember_givenTheirOwnTaggedRoute_dropsTheTeamTagWithIt), so a tag can no
        // longer become stranded via "author leaves the team" the way it could when #3864 wrote
        // this test. TagPolicy::edit()'s fallback to the route's own permissions is still needed
        // though: it covers rows that were already orphaned before that fix shipped (see #3866),
        // which this arranges directly - a team tag on a route whose author was
        // never actually a member of the tag's team - to exercise the
        // `Team::find(...)->isUserMember($user) === false` branch without relying on removeMember().
        $author = null;
        $team   = null;
        $route  = null;
        $tag    = null;

        try {
            // Arrange
            $author = User::factory()->create();
            $team   = $this->createTeam();
            $route  = DungeonRoute::factory()->create(['author_id' => $author->id]);
            $tag    = $this->createTeamTag($team, $route);

            // Act
            $response = $this->actingAs($author)->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertNoContent();
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp(tag: $tag, route: $route, team: $team, users: [$author]);
        }
    }

    #[Test]
    public function delete_givenTeamTagWhoseTeamNoLongerExists_deletesItRatherThanErroring(): void
    {
        // A dangling context_id used to reach Team::findOrFail() inside TagPolicy and throw a 404 at
        // every caller, which would have made the tag undeletable by anyone once the gate was added.
        $author = null;
        $route  = null;
        $tag    = null;

        try {
            // Arrange
            $author = User::factory()->create();
            $team   = $this->createTeam();
            $route  = DungeonRoute::factory()->create(['author_id' => $author->id]);
            $tag    = $this->createTeamTag($team, $route);

            $teamId = $team->id;
            Team::where('id', $teamId)->delete();
            $this->assertNull(Team::find($teamId), 'Arrange failed: team still exists');

            // Act
            $response = $this->actingAs($author)->delete(sprintf('/ajax/tag/%d', $tag->id));

            // Assert
            $response->assertNoContent();
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp(tag: $tag, route: $route, users: [$author]);
        }
    }

    private function createUserTagFor(User $user): Tag
    {
        return Tag::create([
            'context_id'      => $user->id,
            'context_class'   => User::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
            'model_id'        => null,
            'model_class'     => null,
            'name'            => sprintf('test-tag-%s', fake()->uuid()),
            'color'           => null,
        ]);
    }

    private function createTeamTag(Team $team, DungeonRoute $dungeonRoute): Tag
    {
        return Tag::create([
            'context_id'      => $team->id,
            'context_class'   => Team::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM],
            'model_id'        => $dungeonRoute->id,
            'model_class'     => DungeonRoute::class,
            'name'            => sprintf('test-team-tag-%s', fake()->uuid()),
            'color'           => null,
        ]);
    }

    private function createTeam(): Team
    {
        return Team::create([
            'public_key'   => fake()->unique()->uuid(),
            'name'         => sprintf('test-team-%s', fake()->uuid()),
            'description'  => 'Created by AjaxTagControllerTest',
            'invite_code'  => fake()->unique()->uuid(),
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
    }

    /**
     * @param array<int, User|null> $users
     */
    private function cleanUp(?Tag $tag = null, ?DungeonRoute $route = null, ?Team $team = null, array $users = []): void
    {
        if ($tag !== null) {
            Tag::where('id', $tag->id)->delete();
        }

        if ($route !== null) {
            DungeonRoute::where('id', $route->id)->delete();
        }

        if ($team !== null) {
            TeamUser::where('team_id', $team->id)->delete();
            Team::where('id', $team->id)->delete();
        }

        foreach (array_filter($users) as $user) {
            $user->delete();
        }
    }
}
