<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Event;
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

    /**
     * Guards #4264: isUserAdmin() and getNewAdminUponAdminAccountDeletion() both answer from
     * getUserRole(), which re-queries team_users. Asking them *after* removeMember() had deleted
     * the row - as the controller used to - made isUserAdmin() always false, so no successor was
     * ever promoted and the team was left with no admin whatsoever.
     */
    #[Test]
    public function removeMember_givenTheAdminRemovesThemselves_promotesTheHighestRankedMember(): void
    {
        // Arrange - the moderator outranks the plain member, so they are the successor
        $admin = User::factory()->create();
        $admin->addRole(Role::ROLE_USER);

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $admin->id, 'role' => TeamUser::ROLE_ADMIN]);

        $this->actingAs($admin);

        try {
            // Act
            $response = $this->delete(sprintf('/ajax/team/%s/member/%s', $this->team->getRouteKey(), $admin->getRouteKey()));

            // Assert - the team is left with an admin who can still administrate it
            $response->assertNoContent();
            $this->assertSame(
                TeamUser::ROLE_ADMIN,
                TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $this->moderator->id)->value('role'),
            );
        } finally {
            TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $admin->id)->delete();
            $admin->delete();
        }
    }

    /**
     * Guards #4264: removeMember() is transacted internally, but the admin promotion that follows
     * it was not part of that transaction. An admin removing themselves whose promotion of a
     * successor then failed left a team with no admin at all - a state
     * Team::getNewAdminUponAdminAccountDeletion() throws on, so nothing could repair it afterwards.
     */
    #[Test]
    public function removeMember_givenTheAdminPromotionFails_rollsBackTheMemberRemoval(): void
    {
        // Arrange - an admin who is about to remove themselves, leaving the moderator to promote
        $admin = User::factory()->create();
        $admin->addRole(Role::ROLE_USER);

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $admin->id, 'role' => TeamUser::ROLE_ADMIN]);

        $this->actingAs($admin);

        // Fail the promotion, which is a TeamUser role update. The member removal itself is a query
        // builder delete and fires no model events, so this only trips the write that follows it
        TeamUser::updating(static function (): never {
            throw new Exception('Simulated failure promoting the new admin');
        });

        try {
            // Act
            $response = $this->delete(sprintf('/ajax/team/%s/member/%s', $this->team->getRouteKey(), $admin->getRouteKey()));

            // Assert - the removal is undone, so the team still has an admin who can retry
            $response->assertStatus(StatusCode::INTERNAL_SERVER_ERROR);
            $this->assertSame(
                TeamUser::ROLE_ADMIN,
                TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $admin->id)->value('role'),
            );
        } finally {
            // Remove only the listener registered above - TeamUser::flushEventListeners() would also
            // wipe its own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.updating: ' . TeamUser::class);

            TeamUser::query()->where('team_id', $this->team->id)->where('user_id', $admin->id)->delete();
            $admin->delete();
        }
    }

    private function changeRoleUrl(): string
    {
        return sprintf('/ajax/team/%s/changerole', $this->team->getRouteKey());
    }
}
