<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * changeRole() used to read `role` straight off the request with no validation, so a missing or
 * array `role` reached TeamPolicy::changeRole()'s `string $role` parameter and 500'd instead of
 * returning a clean validation error. These tests pin the validation gap shut.
 */
#[Group('Controller')]
#[Group('Team')]
final class AjaxTeamControllerTest extends AjaxPublicTestCase
{
    private Team $team;

    private User $moderator;

    private User $member;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::create([
            'name'         => sprintf('Ajax team test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by AjaxTeamControllerTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);

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
            // Team's "deleting" hook walks members->patreonAdFreeGiveaway, which trips
            // preventLazyLoading unless the chain is eager-loaded first.
            $this->team->load('members.patreonAdFreeGiveaway')->delete();
            $this->member->delete();
            $this->moderator->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function changeRole_givenValidRoleAndUsername_returnsNoContentAndUpdatesRole(): void
    {
        // Act
        $response = $this->put($this->changeRoleUrl(), [
            'username' => $this->member->name,
            'role'     => TeamUser::ROLE_COLLABORATOR,
        ]);

        // Assert
        $response->assertNoContent();
        $this->assertSame(
            TeamUser::ROLE_COLLABORATOR,
            TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $this->member->id)->value('role'),
        );
    }

    #[Test]
    public function changeRole_givenMissingRole_returnsValidationError(): void
    {
        // Act
        $response = $this->put($this->changeRoleUrl(), [
            'username' => $this->member->name,
        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['role']);
    }

    #[Test]
    public function changeRole_givenRoleAsArray_returnsValidationError(): void
    {
        // Act - this exact shape used to TypeError past validation and 500
        $response = $this->put($this->changeRoleUrl(), [
            'username' => $this->member->name,
            'role'     => ['not', 'a', 'string'],
        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['role']);
    }

    #[Test]
    public function changeRole_givenInvalidRoleValue_returnsValidationError(): void
    {
        // Act
        $response = $this->put($this->changeRoleUrl(), [
            'username' => $this->member->name,
            'role'     => 'not_a_real_role',
        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['role']);
    }

    #[Test]
    public function changeRole_givenUnknownUsername_returnsValidationError(): void
    {
        // Act
        $response = $this->put($this->changeRoleUrl(), [
            'username' => 'this_user_does_not_exist',
            'role'     => TeamUser::ROLE_COLLABORATOR,
        ]);

        // Assert
        $response->assertStatus(StatusCode::FOUND);
        $response->assertSessionHasErrors(['username']);
    }

    private function changeRoleUrl(): string
    {
        return sprintf('/ajax/team/%s/changerole', $this->team->getRouteKey());
    }
}
