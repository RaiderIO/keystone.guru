<?php

namespace Tests\Feature\App\Models;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Team')]
final class TeamTest extends PublicTestCase
{
    #[Test]
    public function getAvailableTags_givenOwnTeamsTaggedRoute_returnsTheTag(): void
    {
        $author = null;
        $team   = null;
        $route  = null;
        $tag    = null;

        try {
            // Arrange
            $author = User::factory()->create();
            $team   = $this->createTeam();
            $team->addMember($author, TeamUser::ROLE_MEMBER);
            $route = DungeonRoute::factory()->create([
                'author_id' => $author->id,
                'team_id'   => $team->id,
            ]);
            $tag = $this->createTeamTag($team, $route);

            // Act
            $availableTags = $team->getAvailableTags();

            // Assert
            $this->assertTrue($availableTags->contains('id', $tag->id));
        } finally {
            $this->cleanUp($tag, $route, $team, $author);
        }
    }

    #[Test]
    public function getAvailableTags_givenAnotherTeamsTagOnASharedRoute_excludesIt(): void
    {
        // A route can move between teams (or be re-tagged) while an old tag from a previous team
        // still points at it. getAvailableTags() must not surface that other team's tag as if it
        // were this team's own.
        $author    = null;
        $team      = null;
        $otherTeam = null;
        $route     = null;
        $ownTag    = null;
        $otherTag  = null;

        try {
            // Arrange
            $author    = User::factory()->create();
            $team      = $this->createTeam();
            $otherTeam = $this->createTeam();
            $team->addMember($author, TeamUser::ROLE_MEMBER);
            $route = DungeonRoute::factory()->create([
                'author_id' => $author->id,
                'team_id'   => $team->id,
            ]);
            $ownTag   = $this->createTeamTag($team, $route);
            $otherTag = $this->createTeamTag($otherTeam, $route);

            // Act
            $availableTags = $team->getAvailableTags();

            // Assert
            $this->assertTrue($availableTags->contains('id', $ownTag->id));
            $this->assertFalse($availableTags->contains('id', $otherTag->id));
        } finally {
            $this->cleanUp($otherTag, null, $otherTeam, null);
            $this->cleanUp($ownTag, $route, $team, $author);
        }
    }

    #[Test]
    public function getAvailableTags_givenOwnTeamsUnassignedTagDefinition_returnsIt(): void
    {
        // TeamController::createTag() stores a team's tag definitions through Tag::saveFromRequest(),
        // which writes model_id = NULL. A plain whereIn('model_id', ...) never matches NULL, so a
        // tag definition never applied to a route would otherwise never show up in the autocomplete.
        $team = null;
        $def  = null;

        try {
            // Arrange
            $team = $this->createTeam();
            $def  = $this->createTeamTagDefinition($team);

            // Act
            $availableTags = $team->getAvailableTags();

            // Assert
            $this->assertTrue($availableTags->contains('id', $def->id));
        } finally {
            $this->cleanUp($def, null, $team, null);
        }
    }

    #[Test]
    public function getAvailableTags_givenAnotherTeamsUnassignedTagDefinition_excludesIt(): void
    {
        // A team with no routes/tags of its own would make the negative assertion below pass
        // vacuously (and wouldn't catch the whereNull()/orWhereIn() clause escaping its enclosing
        // where() and collapsing the entire query into a no-op OR), so this team also gets its own
        // definition to prove getAvailableTags() is actually finding *something*.
        $team      = null;
        $otherTeam = null;
        $ownDef    = null;
        $otherDef  = null;

        try {
            // Arrange
            $team      = $this->createTeam();
            $otherTeam = $this->createTeam();
            $ownDef    = $this->createTeamTagDefinition($team);
            $otherDef  = $this->createTeamTagDefinition($otherTeam);

            // Act
            $availableTags = $team->getAvailableTags();

            // Assert
            $this->assertTrue($availableTags->contains('id', $ownDef->id));
            $this->assertFalse($availableTags->contains('id', $otherDef->id));
        } finally {
            $this->cleanUp($otherDef, null, $otherTeam, null);
            $this->cleanUp($ownDef, null, $team, null);
        }
    }

    #[Test]
    public function removeMember_givenTheirOwnTaggedRoute_dropsTheTeamTagWithIt(): void
    {
        $author = null;
        $team   = null;
        $route  = null;
        $tag    = null;

        try {
            // Arrange
            $author = User::factory()->create();
            $team   = $this->createTeam();
            $team->addMember($author, TeamUser::ROLE_MEMBER);
            $route = DungeonRoute::factory()->create([
                'author_id' => $author->id,
                'team_id'   => $team->id,
            ]);
            $tag = $this->createTeamTag($team, $route);

            // Act
            $team->removeMember($author);

            // Assert - the route leaves the team, and the team's tag leaves with it
            $this->assertNull($route->fresh()->team_id);
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp($tag, $route, $team, $author);
        }
    }

    #[Test]
    public function removeMember_givenAnotherMembersTaggedRoute_leavesThatRouteAlone(): void
    {
        // removeMember() only unassigns routes the leaving member authored, so the tag cleanup has
        // to be scoped to exactly those routes rather than to everything the team holds.
        $leaver    = null;
        $staying   = null;
        $team      = null;
        $ownRoute  = null;
        $themRoute = null;
        $ownTag    = null;
        $themTag   = null;

        try {
            // Arrange
            $leaver  = User::factory()->create();
            $staying = User::factory()->create();
            $team    = $this->createTeam();
            $team->addMember($leaver, TeamUser::ROLE_MEMBER);
            $team->addMember($staying, TeamUser::ROLE_MEMBER);

            $ownRoute  = DungeonRoute::factory()->create(['author_id' => $leaver->id, 'team_id' => $team->id]);
            $themRoute = DungeonRoute::factory()->create(['author_id' => $staying->id, 'team_id' => $team->id]);
            $ownTag    = $this->createTeamTag($team, $ownRoute);
            $themTag   = $this->createTeamTag($team, $themRoute);

            // Act
            $team->removeMember($leaver);

            // Assert
            $this->assertDatabaseMissing('tags', ['id' => $ownTag->id]);
            $this->assertDatabaseHas('tags', ['id' => $themTag->id]);
            $this->assertSame($team->id, $themRoute->fresh()->team_id);
        } finally {
            $this->cleanUp($ownTag, $ownRoute, null, $leaver);
            $this->cleanUp($themTag, $themRoute, $team, $staying);
        }
    }

    #[Test]
    public function removeMember_givenAnotherTeamsTagOnTheSameRoute_leavesItAlone(): void
    {
        // The delete is scoped by context_class/context_id as well as by route, so a tag left on the
        // route by a team the route used to belong to must survive this team's cleanup. Without both
        // where() clauses the other team's tag would be collateral.
        $author    = null;
        $team      = null;
        $otherTeam = null;
        $route     = null;
        $ownTag    = null;
        $otherTag  = null;

        try {
            // Arrange
            $author    = User::factory()->create();
            $team      = $this->createTeam();
            $otherTeam = $this->createTeam();
            $team->addMember($author, TeamUser::ROLE_MEMBER);
            $route = DungeonRoute::factory()->create([
                'author_id' => $author->id,
                'team_id'   => $team->id,
            ]);
            $ownTag   = $this->createTeamTag($team, $route);
            $otherTag = $this->createTeamTag($otherTeam, $route);

            // Act
            $team->removeMember($author);

            // Assert
            $this->assertDatabaseMissing('tags', ['id' => $ownTag->id]);
            $this->assertDatabaseHas('tags', ['id' => $otherTag->id]);
        } finally {
            $this->cleanUp($otherTag, null, $otherTeam, null);
            $this->cleanUp($ownTag, $route, $team, $author);
        }
    }

    #[Test]
    public function removeMember_givenAnUnassignedTagDefinition_leavesItAlone(): void
    {
        // The team lives on when a member leaves, so its tag definitions have to survive - only the
        // leaving member's own tag assignments go. This is the counterpart to
        // deleting_givenAnUnassignedTagDefinition_dropsItToo and pins the split between the two.
        $author = null;
        $team   = null;
        $route  = null;
        $tag    = null;
        $def    = null;

        try {
            // Arrange
            $author = User::factory()->create();
            $team   = $this->createTeam();
            $team->addMember($author, TeamUser::ROLE_MEMBER);
            $route = DungeonRoute::factory()->create([
                'author_id' => $author->id,
                'team_id'   => $team->id,
            ]);
            $tag = $this->createTeamTag($team, $route);
            $def = $this->createTeamTagDefinition($team);

            // Act
            $team->removeMember($author);

            // Assert
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
            $this->assertDatabaseHas('tags', ['id' => $def->id]);
        } finally {
            $this->cleanUp($def, null, null, null);
            $this->cleanUp($tag, $route, $team, $author);
        }
    }

    #[Test]
    public function deleting_givenATagStrandedByAnEarlierRemoveMember_stillDropsIt(): void
    {
        // The hook used to clear tags by the routes the team currently holds. A route unassigned by
        // removeMember() is no longer among them, so its tag survived the team's deletion with a
        // context_id pointing at a row that no longer existed.
        $author = null;
        $team   = null;
        $route  = null;
        $tag    = null;

        try {
            // Arrange - build the stranded tag directly, so this test still pins the hook even if
            // removeMember() ever stops cleaning up
            $author = User::factory()->create();
            $team   = $this->createTeam();
            $route  = DungeonRoute::factory()->create(['author_id' => $author->id]);
            $tag    = $this->createTeamTag($team, $route);

            $this->assertCount(0, $team->dungeonRoutes()->get(), 'Arrange failed: route must not be on the team');

            // Act
            $team->delete();
            $team = null;

            // Assert
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp($tag, $route, $team, $author);
        }
    }

    #[Test]
    public function deleting_givenAnUnassignedTagDefinition_dropsItToo(): void
    {
        // TeamController::createTag() stores a team's tag definitions through Tag::saveFromRequest(),
        // which writes model_id = NULL. NULL never matches a SQL IN list, so the old
        // whereIn('model_id', ...) hook left every definition behind - rows whose context_id pointed
        // at a team that no longer existed. Scoping by context is what picks them up.
        $team = null;
        $tag  = null;

        try {
            // Arrange
            $team = $this->createTeam();
            $tag  = $this->createTeamTagDefinition($team);

            // Act
            $team->delete();
            $team = null;

            // Assert
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        } finally {
            $this->cleanUp($tag, null, $team, null);
        }
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

    /**
     * A tag definition as TeamController::createTag() stores it: owned by the team, not yet applied
     * to any route, so model_id/model_class are NULL.
     */
    private function createTeamTagDefinition(Team $team): Tag
    {
        return Tag::create([
            'context_id'      => $team->id,
            'context_class'   => Team::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM],
            'model_id'        => null,
            'model_class'     => null,
            'name'            => sprintf('test-team-tag-def-%s', fake()->uuid()),
            'color'           => null,
        ]);
    }

    private function createTeam(): Team
    {
        return Team::create([
            'public_key'   => fake()->unique()->uuid(),
            'name'         => sprintf('test-team-%s', fake()->uuid()),
            'description'  => 'Created by TeamTest',
            'invite_code'  => fake()->unique()->uuid(),
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
    }

    private function cleanUp(?Tag $tag, ?DungeonRoute $route, ?Team $team, ?User $user): void
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

        $user?->delete();
    }
}
