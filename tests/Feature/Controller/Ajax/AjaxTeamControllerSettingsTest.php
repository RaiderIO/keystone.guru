<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * The team settings endpoints: the default role new members join with and the invite link refresh.
 */
#[Group('Controller')]
#[Group('Team')]
final class AjaxTeamControllerSettingsTest extends AjaxPublicTestCase
{
    private Team $team;

    private User $teamAdmin;

    private User $moderator;

    private User $member;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::create([
            'name'         => sprintf('Ajax team settings test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by AjaxTeamControllerSettingsTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);

        $this->teamAdmin = $this->createTeamMember(TeamUser::ROLE_ADMIN);
        $this->moderator = $this->createTeamMember(TeamUser::ROLE_MODERATOR);
        $this->member    = $this->createTeamMember(TeamUser::ROLE_MEMBER);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->team->delete();
            $this->member->delete();
            $this->moderator->delete();
            $this->teamAdmin->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    #[DataProvider('changeDefaultRole_givenValidRole_dataProvider')]
    public function changeDefaultRole_givenTeamAdminAndValidRole_returnsNoContentAndUpdatesDefaultRole(string $role): void
    {
        // Arrange
        $this->actingAs($this->teamAdmin);

        // Act
        $response = $this->put($this->teamUrl('changedefaultrole'), ['default_role' => $role]);

        // Assert
        $response->assertNoContent();
        $this->assertSame($role, $this->team->fresh()->default_role);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function changeDefaultRole_givenValidRole_dataProvider(): array
    {
        return [
            'member'       => [TeamUser::ROLE_MEMBER],
            'collaborator' => [TeamUser::ROLE_COLLABORATOR],
            'moderator'    => [TeamUser::ROLE_MODERATOR],
            'admin'        => [TeamUser::ROLE_ADMIN],
        ];
    }

    #[Test]
    public function changeDefaultRole_givenTeamModerator_returnsForbiddenAndKeepsDefaultRole(): void
    {
        // Arrange
        $this->actingAs($this->moderator);

        // Act
        $response = $this->put($this->teamUrl('changedefaultrole'), ['default_role' => TeamUser::ROLE_COLLABORATOR]);

        // Assert
        $response->assertForbidden();
        $this->assertSame(TeamUser::ROLE_MEMBER, $this->team->fresh()->default_role);
    }

    #[Test]
    public function changeDefaultRole_givenSiteAdminWhoIsNotInTheTeam_returnsNoContentAndUpdatesDefaultRole(): void
    {
        // Arrange - AjaxPublicTestCase acts as the seeded site admin, who is not a member of this team

        // Act
        $response = $this->put($this->teamUrl('changedefaultrole'), ['default_role' => TeamUser::ROLE_COLLABORATOR]);

        // Assert
        $response->assertNoContent();
        $this->assertSame(TeamUser::ROLE_COLLABORATOR, $this->team->fresh()->default_role);
    }

    #[Test]
    public function changeDefaultRole_givenUserOutsideTheTeam_returnsForbiddenAndKeepsDefaultRole(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $outsider->addRole(Role::ROLE_USER);

        try {
            $this->actingAs($outsider);

            // Act
            $response = $this->put($this->teamUrl('changedefaultrole'), ['default_role' => TeamUser::ROLE_COLLABORATOR]);

            // Assert
            $response->assertForbidden();
            $this->assertSame(TeamUser::ROLE_MEMBER, $this->team->fresh()->default_role);
        } finally {
            $outsider->delete();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[Test]
    #[DataProvider('changeDefaultRole_givenInvalidDefaultRole_dataProvider')]
    public function changeDefaultRole_givenInvalidDefaultRole_returnsValidationErrorAndKeepsDefaultRole(array $payload): void
    {
        // Arrange
        $this->actingAs($this->teamAdmin);

        // Act
        $response = $this->putJson($this->teamUrl('changedefaultrole'), $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['default_role']);
        $this->assertSame(TeamUser::ROLE_MEMBER, $this->team->fresh()->default_role);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function changeDefaultRole_givenInvalidDefaultRole_dataProvider(): array
    {
        return [
            'missing'      => [[]],
            'unknown role' => [['default_role' => 'owner']],
            'array'        => [['default_role' => [TeamUser::ROLE_ADMIN]]],
        ];
    }

    #[Test]
    public function changeRoutePublishing_givenSiteAdmin_returnsMethodNotAllowed(): void
    {
        // Arrange - AjaxPublicTestCase acts as the seeded site admin; the GET-only fallback route
        // matches every unknown path, so a PUT to one answers 405 rather than 404

        // Act
        $response = $this->putJson($this->teamUrl('routepublishing'), ['enabled' => 1]);

        // Assert
        $response->assertMethodNotAllowed();
    }

    #[Test]
    #[DataProvider('refreshInviteLink_givenModeratorOrHigher_dataProvider')]
    public function refreshInviteLink_givenModeratorOrHigher_returnsNewInviteLink(string $actor): void
    {
        // Arrange
        $this->actingAs($this->{$actor});
        $oldInviteCode = $this->team->invite_code;

        // Act
        $response = $this->postJson($this->teamUrl('refreshlink'));

        // Assert
        $response->assertOk();
        $newInviteCode = $this->team->fresh()->invite_code;
        $this->assertNotSame($oldInviteCode, $newInviteCode);
        $response->assertExactJson(['new_invite_link' => route('team.invite', ['invitecode' => $newInviteCode])]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function refreshInviteLink_givenModeratorOrHigher_dataProvider(): array
    {
        return [
            'team admin' => ['teamAdmin'],
            'moderator'  => ['moderator'],
        ];
    }

    #[Test]
    public function refreshInviteLink_givenGetRequestFromModerator_returnsMethodNotAllowedAndKeepsInviteCode(): void
    {
        // Arrange
        $this->actingAs($this->moderator);
        $oldInviteCode = $this->team->invite_code;

        // Act
        $response = $this->getJson($this->teamUrl('refreshlink'));

        // Assert
        $response->assertMethodNotAllowed();
        $this->assertSame($oldInviteCode, $this->team->fresh()->invite_code);
    }

    #[Test]
    public function refreshInviteLink_givenPlainMember_returnsForbiddenAndKeepsInviteCode(): void
    {
        // Arrange
        $this->actingAs($this->member);

        // Act
        $response = $this->postJson($this->teamUrl('refreshlink'));

        // Assert
        $response->assertForbidden();
        $this->assertSame($this->team->invite_code, $this->team->fresh()->invite_code);
    }

    #[Test]
    public function refreshInviteLink_givenSiteAdminWhoIsNotInTheTeam_returnsNewInviteLink(): void
    {
        // Arrange - AjaxPublicTestCase acts as the seeded site admin, who is not a member of this team
        $oldInviteCode = $this->team->invite_code;

        // Act
        $response = $this->postJson($this->teamUrl('refreshlink'));

        // Assert
        $response->assertOk();
        $this->assertNotSame($oldInviteCode, $this->team->fresh()->invite_code);
    }

    #[Test]
    public function refreshInviteLink_givenUserOutsideTheTeam_returnsForbiddenAndKeepsInviteCode(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $outsider->addRole(Role::ROLE_USER);

        try {
            $this->actingAs($outsider);

            // Act
            $response = $this->postJson($this->teamUrl('refreshlink'));

            // Assert
            $response->assertForbidden();
            $this->assertSame($this->team->invite_code, $this->team->fresh()->invite_code);
        } finally {
            $outsider->delete();
        }
    }

    #[Test]
    public function refreshInviteLink_givenGuest_returnsUnauthorized(): void
    {
        // Arrange
        auth()->logout();

        // Act
        $response = $this->postJson($this->teamUrl('refreshlink'));

        // Assert
        $response->assertUnauthorized();
        $this->assertSame($this->team->invite_code, $this->team->fresh()->invite_code);
    }

    private function createTeamMember(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    private function teamUrl(string $action): string
    {
        return sprintf('/ajax/team/%s/%s', $this->team->getRouteKey(), $action);
    }
}
