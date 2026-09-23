<?php

namespace Tests\Feature\Controller\Team;

use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The team pages outside the edit page: the invite link, accepting it, creating, updating and
 * deleting a team.
 */
#[Group('Controller')]
#[Group('Team')]
final class TeamControllerMembershipTest extends PublicTestCase
{
    private Team $team;

    private User $teamAdmin;

    private User $moderator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::create([
            'name'                     => sprintf('Team membership test %s', uniqid()),
            'public_key'               => Team::generateRandomPublicKey(),
            'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'              => 'Created by TeamControllerMembershipTest',
            'icon_file_id'             => -1,
            'default_role'             => TeamUser::ROLE_COLLABORATOR,
            'route_publishing_enabled' => false,
        ]);

        $this->teamAdmin = $this->createUser();
        $this->moderator = $this->createUser();

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $this->teamAdmin->id, 'role' => TeamUser::ROLE_ADMIN]);
        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $this->moderator->id, 'role' => TeamUser::ROLE_MODERATOR]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            // The team is gone when a test deleted it
            Team::query()->whereKey($this->team->id)->first()?->load('members.patreonAdFreeGiveaway')->delete();
            $this->moderator->delete();
            $this->teamAdmin->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function invite_givenUnknownInviteCode_returnsNotFound(): void
    {
        // Act
        $response = $this->get(route('team.invite', ['invitecode' => 'no-such-invite-code']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function invite_givenGuest_rendersTheInvitationWithALoginPrompt(): void
    {
        // Act
        $response = $this->get(route('team.invite', ['invitecode' => $this->team->invite_code]));

        // Assert
        $response->assertOk();
        $response->assertSee(sprintf(__('view_team.invite.invited_to_join'), $this->team->name));
        $response->assertSee(__('view_team.invite.login_or_register_to_accept'));
        $response->assertDontSee(route('team.invite.accept', ['invitecode' => $this->team->invite_code]));
    }

    #[Test]
    public function invite_givenUserOutsideTheTeam_rendersTheAcceptLink(): void
    {
        // Arrange
        $outsider = $this->createUser();

        try {
            $this->actingAs($outsider);

            // Act
            $response = $this->get(route('team.invite', ['invitecode' => $this->team->invite_code]));

            // Assert
            $response->assertOk();
            $response->assertSee(sprintf(__('view_team.invite.invited_to_join'), $this->team->name));
            $response->assertSee(route('team.invite.accept', ['invitecode' => $this->team->invite_code]));
        } finally {
            $outsider->delete();
        }
    }

    #[Test]
    public function invite_givenExistingMember_rendersAlreadyAMember(): void
    {
        // Arrange
        $this->actingAs($this->moderator);

        // Act
        $response = $this->get(route('team.invite', ['invitecode' => $this->team->invite_code]));

        // Assert
        $response->assertOk();
        $response->assertSee(sprintf(__('view_team.invite.already_a_member'), $this->team->name));
        $response->assertDontSee(route('team.invite.accept', ['invitecode' => $this->team->invite_code]));
    }

    #[Test]
    public function inviteaccept_givenUserOutsideTheTeam_addsThemWithTheDefaultRoleAndRedirectsToTheTeam(): void
    {
        // Arrange
        $outsider = $this->createUser();

        try {
            $this->actingAs($outsider);

            // Act
            $response = $this->get(route('team.invite.accept', ['invitecode' => $this->team->invite_code]));

            // Assert
            $response->assertRedirect(route('team.edit', ['team' => $this->team]));
            $response->assertSessionHas('status', sprintf(__('controller.team.flash.invite_accept_success'), $this->team->name));
            $this->assertSame(TeamUser::ROLE_COLLABORATOR, $this->roleOf($outsider));
        } finally {
            TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $outsider->id)->delete();
            $outsider->delete();
        }
    }

    #[Test]
    public function inviteaccept_givenExistingMember_keepsTheirRoleAndAddsNoSecondMembership(): void
    {
        // Arrange - the default role is lower than the moderator's own
        $this->actingAs($this->moderator);

        // Act
        $response = $this->get(route('team.invite.accept', ['invitecode' => $this->team->invite_code]));

        // Assert
        $response->assertOk();
        $response->assertSee(sprintf(__('view_team.invite.already_a_member'), $this->team->name));
        $this->assertSame(
            [TeamUser::ROLE_MODERATOR],
            TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $this->moderator->id)->pluck('role')->all(),
        );
    }

    #[Test]
    public function inviteaccept_givenGuest_redirectsToLoginAndAddsNoMember(): void
    {
        // Arrange
        $memberCount = TeamUser::query()->where('team_id', $this->team->id)->count();

        // Act
        $response = $this->get(route('team.invite.accept', ['invitecode' => $this->team->invite_code]));

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertSame($memberCount, TeamUser::query()->where('team_id', $this->team->id)->count());
    }

    #[Test]
    public function savenew_givenValidTeam_createsTheTeamWithTheCreatorAsAdmin(): void
    {
        // Arrange
        $creator = $this->createUser();
        $name    = sprintf('New team %s', uniqid());

        try {
            $this->actingAs($creator);

            // Act
            $response = $this->post(route('team.savenew'), [
                'name'        => $name,
                'description' => 'A brand new team',
            ]);

            // Assert
            $team = Team::query()->where('name', $name)->first();
            $this->assertNotNull($team);
            $response->assertRedirect(route('team.edit', ['team' => $team]));
            $response->assertSessionHas('status', __('controller.team.flash.team_created'));
            $this->assertSame('A brand new team', $team->description);
            $this->assertSame(TeamUser::ROLE_ADMIN, $team->getUserRole($creator));
        } finally {
            Team::query()->where('name', $name)->first()?->load('members.patreonAdFreeGiveaway')->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function savenew_givenUserWhoIsNoSiteAdmin_ignoresTheRoutePublishingField(): void
    {
        // Arrange - only whoever may change route publishing gets to set it while creating
        $creator = $this->createUser();
        $name    = sprintf('New team %s', uniqid());

        try {
            $this->actingAs($creator);

            // Act
            $this->post(route('team.savenew'), [
                'name'                     => $name,
                'description'              => '',
                'route_publishing_enabled' => 1,
            ]);

            // Assert
            $team = Team::query()->where('name', $name)->first();
            $this->assertNotNull($team);
            $this->assertFalse((bool)$team->route_publishing_enabled);
        } finally {
            Team::query()->where('name', $name)->first()?->load('members.patreonAdFreeGiveaway')->delete();
            $creator->delete();
        }
    }

    #[Test]
    #[DataProvider('savenew_givenInvalidName_dataProvider')]
    public function savenew_givenInvalidName_returnsValidationErrorAndCreatesNoTeam(?string $name): void
    {
        // Arrange
        $creator   = $this->createUser();
        $teamCount = Team::query()->count();

        try {
            $this->actingAs($creator);

            // Act
            $response = $this->post(route('team.savenew'), [
                'name'        => $name ?? '',
                'description' => '',
            ]);

            // Assert
            $response->assertSessionHasErrors(['name']);
            $this->assertSame($teamCount, Team::query()->count());
        } finally {
            $creator->delete();
        }
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function savenew_givenInvalidName_dataProvider(): array
    {
        return [
            'missing'  => [null],
            'too long' => [str_repeat('a', 33)],
        ];
    }

    #[Test]
    public function savenew_givenNameOfAnExistingTeam_returnsValidationErrorAndCreatesNoTeam(): void
    {
        // Arrange
        $creator   = $this->createUser();
        $teamCount = Team::query()->count();

        try {
            $this->actingAs($creator);

            // Act
            $response = $this->post(route('team.savenew'), [
                'name'        => $this->team->name,
                'description' => '',
            ]);

            // Assert
            $response->assertSessionHasErrors(['name']);
            $this->assertSame($teamCount, Team::query()->count());
        } finally {
            $creator->delete();
        }
    }

    #[Test]
    public function update_givenTeamMember_updatesTheDescriptionAndRendersTheEditPage(): void
    {
        // Arrange
        $this->actingAs($this->moderator);

        // Act
        $response = $this->patch(route('team.update', ['team' => $this->team]), [
            'description' => 'Updated description',
        ]);

        // Assert
        $response->assertOk();
        $response->assertSessionHas('status', __('controller.team.flash.team_updated'));
        $this->assertSame('Updated description', $this->team->fresh()->description);
    }

    #[Test]
    public function update_givenUserOutsideTheTeam_returnsForbiddenAndKeepsTheTeam(): void
    {
        // Arrange
        $outsider = $this->createUser();

        try {
            $this->actingAs($outsider);

            // Act
            $response = $this->patch(route('team.update', ['team' => $this->team]), [
                'description' => 'Hijacked',
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertSame('Created by TeamControllerMembershipTest', $this->team->fresh()->description);
        } finally {
            $outsider->delete();
        }
    }

    #[Test]
    public function update_givenNameOfAnotherTeam_returnsValidationErrorAndKeepsTheTeam(): void
    {
        // Arrange
        $otherTeam = null;
        $this->actingAs($this->teamAdmin);

        try {
            $otherTeam = Team::create([
                'name'         => sprintf('Other team %s', uniqid()),
                'public_key'   => Team::generateRandomPublicKey(),
                'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
                'description'  => '',
                'icon_file_id' => -1,
                'default_role' => TeamUser::ROLE_MEMBER,
            ]);

            // Act
            $response = $this->patch(route('team.update', ['team' => $this->team]), [
                'name'        => $otherTeam->name,
                'description' => 'Should not be saved',
            ]);

            // Assert
            $response->assertSessionHasErrors(['name']);
            $this->assertSame('Created by TeamControllerMembershipTest', $this->team->fresh()->description);
        } finally {
            $otherTeam?->delete();
        }
    }

    #[Test]
    public function delete_givenTeamAdminAsTheOnlyMember_deletesTheTeamAndRedirectsToTheTeamList(): void
    {
        // Arrange
        TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $this->moderator->id)->delete();
        $this->actingAs($this->teamAdmin);

        // Act
        $response = $this->delete(route('team.delete', ['team' => $this->team]));

        // Assert
        $response->assertRedirect(route('team.list'));
        $this->assertFalse(Team::query()->whereKey($this->team->id)->exists());
        $this->assertFalse(TeamUser::query()->where('team_id', $this->team->id)->exists());
    }

    #[Test]
    public function delete_givenModerator_returnsForbiddenAndKeepsTheTeam(): void
    {
        // Arrange
        $this->actingAs($this->moderator);

        // Act
        $response = $this->delete(route('team.delete', ['team' => $this->team]));

        // Assert
        $response->assertForbidden();
        $this->assertTrue(Team::query()->whereKey($this->team->id)->exists());
    }

    #[Test]
    public function delete_givenUserOutsideTheTeam_returnsForbiddenAndKeepsTheTeam(): void
    {
        // Arrange
        $outsider = $this->createUser();

        try {
            $this->actingAs($outsider);

            // Act
            $response = $this->delete(route('team.delete', ['team' => $this->team]));

            // Assert
            $response->assertForbidden();
            $this->assertTrue(Team::query()->whereKey($this->team->id)->exists());
        } finally {
            $outsider->delete();
        }
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    private function roleOf(User $user): ?string
    {
        return TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $user->id)->value('role');
    }
}
