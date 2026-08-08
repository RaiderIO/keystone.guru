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
