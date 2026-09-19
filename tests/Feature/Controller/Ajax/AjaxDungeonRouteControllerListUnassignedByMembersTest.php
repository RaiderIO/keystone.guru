<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * /ajax/routes with team_public_key and available=1: the routes the route picker's
 * 'unassigned_by_members' source scope lists - routes of the team's members that are in no team yet.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
#[Group('Team')]
final class AjaxDungeonRouteControllerListUnassignedByMembersTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    private Team $team;

    private User $moderator;

    private User $member;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = $this->createTeam();

        $this->moderator = User::factory()->create();
        $this->moderator->addRole(Role::ROLE_USER);

        $this->member = User::factory()->create();
        $this->member->addRole(Role::ROLE_USER);

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $this->moderator->id, 'role' => TeamUser::ROLE_MODERATOR]);
        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $this->member->id, 'role' => TeamUser::ROLE_MEMBER]);

        $this->actingAs($this->moderator);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->team->load('members.patreonAdFreeGiveaway')->delete();
            $this->member->delete();
            $this->moderator->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function get_givenUnassignedRoutesOfTeamMembers_returnsThem(): void
    {
        // Arrange
        $memberRoute    = $this->createRoute($this->member);
        $moderatorRoute = $this->createRoute($this->moderator, [
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
        ]);

        try {
            // Act
            $response = $this->get($this->unassignedByMembersQuery());

            // Assert
            $response->assertOk();
            $this->assertEqualsCanonicalizing(
                [$memberRoute->public_key, $moderatorRoute->public_key],
                array_column($response->json('data'), 'public_key'),
            );
        } finally {
            $memberRoute->delete();
            $moderatorRoute->delete();
        }
    }

    #[Test]
    public function get_givenRoutesThatAreNotAddable_leavesThemOut(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $outsider->addRole(Role::ROLE_USER);
        $otherTeam = $this->createTeam();

        $addable     = $this->createRoute($this->member);
        $onThisTeam  = $this->createRoute($this->member, ['team_id' => $this->team->id]);
        $onOtherTeam = $this->createRoute($this->member, ['team_id' => $otherTeam->id]);
        $byOutsider  = $this->createRoute($outsider);
        $unpublished = $this->createRoute($this->member, [
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            // Act
            $response = $this->get($this->unassignedByMembersQuery());

            // Assert
            $response->assertOk();
            $this->assertSame([$addable->public_key], array_column($response->json('data'), 'public_key'));
        } finally {
            $addable->delete();
            $onThisTeam->delete();
            $onOtherTeam->delete();
            $byOutsider->delete();
            $unpublished->delete();
            $otherTeam->load('members.patreonAdFreeGiveaway')->delete();
            $outsider->delete();
        }
    }

    #[Test]
    public function get_givenANonMember_returnsForbidden(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $outsider->addRole(Role::ROLE_USER);
        $this->actingAs($outsider);

        try {
            // Act
            $response = $this->get($this->unassignedByMembersQuery());

            // Assert
            $response->assertForbidden();
        } finally {
            $outsider->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createRoute(User $author, array $attributes = []): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, dungeonActive: true);

        return DungeonRoute::factory()->create(array_merge([
            'author_id'          => $author->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            // A sandbox route never shows up in a route table
            'expires_at' => null,
        ], $attributes));
    }

    private function createTeam(): Team
    {
        return Team::create([
            'name'         => sprintf('Unassigned by members test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by AjaxDungeonRouteControllerListUnassignedByMembersTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
    }

    private function unassignedByMembersQuery(): string
    {
        return sprintf('/ajax/routes?%s', http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 'title',
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => '', 'regex' => 'false'],
                ],
            ],
            'order'           => [['column' => 0, 'dir' => 'asc']],
            'search'          => ['value' => '', 'regex' => 'false'],
            'team_public_key' => $this->team->public_key,
            'available'       => 1,
        ]));
    }
}
