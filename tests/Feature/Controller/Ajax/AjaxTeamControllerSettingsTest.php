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
 * The team settings endpoints: the default role new members join with, the route publishing toggle
 * and the invite link refresh.
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
            'name'                     => sprintf('Ajax team settings test %s', uniqid()),
            'public_key'               => Team::generateRandomPublicKey(),
            'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'              => 'Created by AjaxTeamControllerSettingsTest',
            'icon_file_id'             => -1,
            'default_role'             => TeamUser::ROLE_MEMBER,
            'route_publishing_enabled' => false,
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
    public function changeDefaultRole_givenSiteAdminWhoIsNotInTheTeam_returnsForbidden(): void
    {
        // Arrange - AjaxPublicTestCase acts as the seeded site admin, who is not a member of this team

        // Act
        $response = $this->put($this->teamUrl('changedefaultrole'), ['default_role' => TeamUser::ROLE_COLLABORATOR]);

        // Assert
        $response->assertForbidden();
        $this->assertSame(TeamUser::ROLE_MEMBER, $this->team->fresh()->default_role);
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
    #[DataProvider('changeRoutePublishing_givenValidValue_dataProvider')]
    public function changeRoutePublishing_givenSiteAdmin_returnsNoContentAndUpdatesSetting(int $enabled, bool $expected): void
    {
        // Arrange - route publishing is a site-admin setting, so the site admin need not be in the team
        $this->team->update(['route_publishing_enabled' => !$expected]);

        // Act
        $response = $this->put($this->teamUrl('routepublishing'), ['enabled' => $enabled]);

        // Assert
        $response->assertNoContent();
        $this->assertSame($expected, (bool)$this->team->fresh()->route_publishing_enabled);
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function changeRoutePublishing_givenValidValue_dataProvider(): array
    {
        return [
            'enable'  => [1, true],
            'disable' => [0, false],
        ];
    }

    #[Test]
    public function changeRoutePublishing_givenTeamAdminWhoIsNoSiteAdmin_returnsForbiddenAndKeepsSetting(): void
    {
        // Arrange
        $this->actingAs($this->teamAdmin);

        // Act
        $response = $this->put($this->teamUrl('routepublishing'), ['enabled' => 1]);

        // Assert
        $response->assertForbidden();
        $this->assertFalse((bool)$this->team->fresh()->route_publishing_enabled);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[Test]
    #[DataProvider('changeRoutePublishing_givenInvalidValue_dataProvider')]
    public function changeRoutePublishing_givenInvalidValue_returnsValidationErrorAndKeepsSetting(array $payload): void
    {
        // Act
        $response = $this->putJson($this->teamUrl('routepublishing'), $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['enabled']);
        $this->assertFalse((bool)$this->team->fresh()->route_publishing_enabled);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function changeRoutePublishing_givenInvalidValue_dataProvider(): array
    {
        return [
            'missing'      => [[]],
            'out of range' => [['enabled' => 2]],
            'word'         => [['enabled' => 'yes']],
        ];
    }

    #[Test]
    #[DataProvider('refreshInviteLink_givenModeratorOrHigher_dataProvider')]
    public function refreshInviteLink_givenModeratorOrHigher_returnsNewInviteLink(string $actor): void
    {
        // Arrange
        $this->actingAs($this->{$actor});
        $oldInviteCode = $this->team->invite_code;

        // Act
        $response = $this->getJson($this->teamUrl('refreshlink'));

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
    public function refreshInviteLink_givenPlainMember_returnsForbiddenAndKeepsInviteCode(): void
    {
        // Arrange
        $this->actingAs($this->member);

        // Act
        $response = $this->getJson($this->teamUrl('refreshlink'));

        // Assert
        $response->assertForbidden();
        $this->assertSame($this->team->invite_code, $this->team->fresh()->invite_code);
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
            $response = $this->getJson($this->teamUrl('refreshlink'));

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
        $response = $this->getJson($this->teamUrl('refreshlink'));

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
