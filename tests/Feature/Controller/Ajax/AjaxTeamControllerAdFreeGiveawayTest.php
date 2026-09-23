<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * POST/DELETE /ajax/team/{team}/member/{user}/adfree: a patron with the ad-free team members benefit
 * hands one of their ad-free giveaways to a teammate, and takes it back again.
 */
#[Group('Controller')]
#[Group('Team')]
final class AjaxTeamControllerAdFreeGiveawayTest extends AjaxPublicTestCase
{
    private Team $team;

    private User $giver;

    private User $receiver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::create([
            'name'         => sprintf('Ajax team ad-free test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by AjaxTeamControllerAdFreeGiveawayTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);

        $this->giver    = $this->createTeamMember(TeamUser::ROLE_ADMIN);
        $this->receiver = $this->createTeamMember(TeamUser::ROLE_MEMBER);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            PatreonAdFreeGiveaway::query()
                ->whereIn('giver_user_id', [$this->giver->id, $this->receiver->id])
                ->orWhereIn('receiver_user_id', [$this->giver->id, $this->receiver->id])
                ->delete();

            $this->team->load('members.patreonAdFreeGiveaway')->delete();
            $this->receiver->delete();
            $this->giver->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function addAdFreeGiveaway_givenPatronWithGiveawaysLeft_returnsCreatedAndCreatesGiveaway(): void
    {
        // Arrange
        $this->grantPatreonBenefit($this->giver, PatreonBenefit::AD_FREE_TEAM_MEMBERS);
        $this->actingAs($this->giver);

        // Act
        $response = $this->postJson($this->adFreeUrl($this->receiver));

        // Assert
        $response->assertCreated();
        $response->assertJson([
            'giver_user_id'    => $this->giver->id,
            'receiver_user_id' => $this->receiver->id,
        ]);
        $this->assertTrue($this->receiver->fresh()->hasAdFreeGiveaway());
    }

    #[Test]
    public function addAdFreeGiveaway_givenGiverWithoutTheBenefit_returnsUnprocessableAndCreatesNothing(): void
    {
        // Arrange - a plain team member has no ad-free giveaways to hand out at all
        $this->actingAs($this->giver);

        // Act
        $response = $this->postJson($this->adFreeUrl($this->receiver));

        // Assert
        $response->assertUnprocessable();
        $this->assertFalse($this->receiver->fresh()->hasAdFreeGiveaway());
    }

    #[Test]
    public function addAdFreeGiveaway_givenGiverAtTheGiveawayLimit_returnsUnprocessableAndCreatesNothing(): void
    {
        // Arrange - one giveaway allowed, and it is already spent on someone else
        config(['keystoneguru.patreon.ad_free_giveaways' => 1]);
        $this->grantPatreonBenefit($this->giver, PatreonBenefit::AD_FREE_TEAM_MEMBERS);
        $earlierReceiver = User::factory()->create();

        try {
            PatreonAdFreeGiveaway::create(['giver_user_id' => $this->giver->id, 'receiver_user_id' => $earlierReceiver->id]);
            $this->actingAs($this->giver);

            // Act
            $response = $this->postJson($this->adFreeUrl($this->receiver));

            // Assert
            $response->assertUnprocessable();
            $this->assertFalse($this->receiver->fresh()->hasAdFreeGiveaway());
        } finally {
            PatreonAdFreeGiveaway::query()->where('receiver_user_id', $earlierReceiver->id)->delete();
            $earlierReceiver->delete();
        }
    }

    #[Test]
    public function addAdFreeGiveaway_givenReceiverWhoIsAdFreeThroughPatreon_returnsUnprocessableAndCreatesNothing(): void
    {
        // Arrange
        $this->grantPatreonBenefit($this->giver, PatreonBenefit::AD_FREE_TEAM_MEMBERS);
        $this->grantPatreonBenefit($this->receiver, PatreonBenefit::AD_FREE);
        $this->actingAs($this->giver);

        // Act
        $response = $this->postJson($this->adFreeUrl($this->receiver));

        // Assert
        $response->assertUnprocessable();
        $this->assertFalse($this->receiver->fresh()->hasAdFreeGiveaway());
    }

    #[Test]
    public function addAdFreeGiveaway_givenReceiverWithAnExistingGiveaway_returnsUnprocessableAndKeepsTheExistingOne(): void
    {
        // Arrange - someone outside this team already gave the receiver a giveaway
        $this->grantPatreonBenefit($this->giver, PatreonBenefit::AD_FREE_TEAM_MEMBERS);
        $otherGiver = User::factory()->create();

        try {
            PatreonAdFreeGiveaway::create(['giver_user_id' => $otherGiver->id, 'receiver_user_id' => $this->receiver->id]);
            $this->actingAs($this->giver);

            // Act
            $response = $this->postJson($this->adFreeUrl($this->receiver));

            // Assert
            $response->assertUnprocessable();
            $this->assertSame(
                [$otherGiver->id],
                PatreonAdFreeGiveaway::query()->where('receiver_user_id', $this->receiver->id)->pluck('giver_user_id')->all(),
            );
        } finally {
            PatreonAdFreeGiveaway::query()->where('giver_user_id', $otherGiver->id)->delete();
            $otherGiver->delete();
        }
    }

    #[Test]
    public function addAdFreeGiveaway_givenGiverOutsideTheTeam_returnsForbiddenAndCreatesNothing(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $outsider->addRole(Role::ROLE_USER);

        try {
            $this->grantPatreonBenefit($outsider, PatreonBenefit::AD_FREE_TEAM_MEMBERS);
            $this->actingAs($outsider);

            // Act
            $response = $this->postJson($this->adFreeUrl($this->receiver));

            // Assert
            $response->assertForbidden();
            $this->assertFalse($this->receiver->fresh()->hasAdFreeGiveaway());
        } finally {
            $outsider->delete();
        }
    }

    #[Test]
    public function addAdFreeGiveaway_givenGuest_returnsUnauthorized(): void
    {
        // Arrange
        auth()->logout();

        // Act
        $response = $this->postJson($this->adFreeUrl($this->receiver));

        // Assert
        $response->assertUnauthorized();
        $this->assertFalse($this->receiver->fresh()->hasAdFreeGiveaway());
    }

    #[Test]
    public function removeAdFreeGiveaway_givenTheGiver_returnsNoContentAndDeletesGiveaway(): void
    {
        // Arrange
        PatreonAdFreeGiveaway::create(['giver_user_id' => $this->giver->id, 'receiver_user_id' => $this->receiver->id]);
        $this->actingAs($this->giver);

        // Act
        $response = $this->deleteJson($this->adFreeUrl($this->receiver));

        // Assert
        $response->assertNoContent();
        $this->assertFalse($this->receiver->fresh()->hasAdFreeGiveaway());
    }

    #[Test]
    public function removeAdFreeGiveaway_givenTeamMemberWhoIsNotTheGiver_returnsForbiddenAndKeepsGiveaway(): void
    {
        // Arrange - the receiver's giveaway came from someone else, a teammate may not revoke it
        $otherGiver = User::factory()->create();

        try {
            PatreonAdFreeGiveaway::create(['giver_user_id' => $otherGiver->id, 'receiver_user_id' => $this->receiver->id]);
            $this->actingAs($this->giver);

            // Act
            $response = $this->deleteJson($this->adFreeUrl($this->receiver));

            // Assert
            $response->assertForbidden();
            $this->assertTrue($this->receiver->fresh()->hasAdFreeGiveaway());
        } finally {
            PatreonAdFreeGiveaway::query()->where('giver_user_id', $otherGiver->id)->delete();
            $otherGiver->delete();
        }
    }

    #[Test]
    public function removeAdFreeGiveaway_givenReceiverWithoutGiveaway_returnsUnprocessable(): void
    {
        // Arrange
        $this->actingAs($this->giver);

        // Act
        $response = $this->deleteJson($this->adFreeUrl($this->receiver));

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function removeAdFreeGiveaway_givenGiverOutsideTheTeam_returnsForbiddenAndKeepsGiveaway(): void
    {
        // Arrange - even the giver needs to be in the team whose page they revoke it through
        $outsideGiver = User::factory()->create();
        $outsideGiver->addRole(Role::ROLE_USER);

        try {
            PatreonAdFreeGiveaway::create(['giver_user_id' => $outsideGiver->id, 'receiver_user_id' => $this->receiver->id]);
            $this->actingAs($outsideGiver);

            // Act
            $response = $this->deleteJson($this->adFreeUrl($this->receiver));

            // Assert
            $response->assertForbidden();
            $this->assertTrue($this->receiver->fresh()->hasAdFreeGiveaway());
        } finally {
            PatreonAdFreeGiveaway::query()->where('giver_user_id', $outsideGiver->id)->delete();
            $outsideGiver->delete();
        }
    }

    private function createTeamMember(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    /**
     * The link and its benefits are dropped again by User::deleting.
     */
    private function grantPatreonBenefit(User $user, string $benefit): void
    {
        $patreonUserLink = PatreonUserLink::factory()->manuallyGranted()->create(['user_id' => $user->id]);
        $user->update(['patreon_user_link_id' => $patreonUserLink->id]);

        PatreonUserBenefit::create([
            'patreon_user_link_id' => $patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[$benefit],
        ]);
    }

    private function adFreeUrl(User $user): string
    {
        return sprintf('/ajax/team/%s/member/%s/adfree', $this->team->getRouteKey(), $user->getRouteKey());
    }
}
