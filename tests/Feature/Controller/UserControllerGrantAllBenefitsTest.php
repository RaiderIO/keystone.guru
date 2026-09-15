<?php

namespace Tests\Feature\Controller;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonManualGrant;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
#[Group('Patreon')]
final class UserControllerGrantAllBenefitsTest extends PublicTestCase
{
    private const string REASON = 'Bought the top tier but it never applied';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function grantAllBenefits_givenUserWithoutPatreonLink_createsFabricatedLinkAndGrantRecord(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->post("/admin/user/{$user->id}/grantAllBenefits", ['reason' => self::REASON]);

            // Assert
            $response->assertRedirect(route('admin.users'));

            $user->refresh();
            $this->assertNotNull($user->patreon_user_link_id);
            $this->assertSame(PatreonUserLink::PERMANENT_TOKEN, $user->patreonUserLink->refresh_token);
            $this->assertSame(count(PatreonBenefit::ALL), PatreonUserBenefit::query()
                ->where('patreon_user_link_id', $user->patreon_user_link_id)
                ->count());

            $this->assertDatabaseHas('patreon_manual_grants', [
                'user_id'            => $user->id,
                'granted_by_user_id' => 1,
                'reason'             => self::REASON,
                'revoked_at'         => null,
            ]);
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function grantAllBenefits_givenUserWithRealPatreonLink_keepsTheLinkTokensIntact(): void
    {
        // Arrange - the point of the grant on an already-linked user is that it overrides their real
        // tier without breaking their Patreon link. Rewriting the tokens would break the OAuth refresh,
        // and any write to that row would also trip its ON UPDATE CURRENT_TIMESTAMP expires_at (#4385)
        $user            = User::factory()->create();
        $expiresAt       = Carbon::now()->addDay()->startOfSecond();
        $patreonUserLink = $this->createRealPatreonLink($user, $expiresAt);

        try {
            // Act
            $response = $this->post("/admin/user/{$user->id}/grantAllBenefits", ['reason' => self::REASON]);

            // Assert
            $response->assertRedirect(route('admin.users'));

            $patreonUserLink->refresh();
            $this->assertSame('real-refresh-token', $patreonUserLink->refresh_token);
            $this->assertSame('real-access-token', $patreonUserLink->access_token);
            $this->assertSame($expiresAt->toDateTimeString(), $patreonUserLink->expires_at);

            $this->assertSame(count(PatreonBenefit::ALL), PatreonUserBenefit::query()
                ->where('patreon_user_link_id', $patreonUserLink->id)
                ->count());
            $this->assertDatabaseHas('patreon_manual_grants', [
                'user_id'    => $user->id,
                'reason'     => self::REASON,
                'revoked_at' => null,
            ]);
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function grantAllBenefits_givenNoReason_returnsValidationErrorAndGrantsNothing(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->post("/admin/user/{$user->id}/grantAllBenefits", []);

            // Assert
            $response->assertSessionHasErrors('reason');

            $user->refresh();
            $this->assertNull($user->patreon_user_link_id);
            $this->assertDatabaseMissing('patreon_manual_grants', ['user_id' => $user->id]);
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function grantAllBenefits_givenAnExistingActiveGrant_revokesTheOldOneSoOnlyOneIsActive(): void
    {
        // Arrange
        $user = User::factory()->create();

        PatreonManualGrant::factory()->create([
            'user_id' => $user->id,
            'reason'  => 'The original reason',
        ]);

        try {
            // Act
            $response = $this->post("/admin/user/{$user->id}/grantAllBenefits", ['reason' => self::REASON]);

            // Assert
            $response->assertRedirect(route('admin.users'));

            $this->assertSame(1, PatreonManualGrant::query()->active()->where('user_id', $user->id)->count());
            $this->assertDatabaseHas('patreon_manual_grants', [
                'user_id'            => $user->id,
                'reason'             => 'The original reason',
                'revoked_by_user_id' => 1,
            ]);
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function grantAllBenefits_givenTheSameUserTwice_doesNotDuplicateLinksOrBenefits(): void
    {
        // Arrange - a re-submitted grant must be idempotent in what the user ends up holding. Without
        // the row lock in the service, the concurrent version of this leaves two fabricated links and
        // a doubled-up set of benefits; sequentially it is the second read of the link that matters
        $user = User::factory()->create();

        try {
            // Act
            $this->post("/admin/user/{$user->id}/grantAllBenefits", ['reason' => self::REASON]);
            $this->post("/admin/user/{$user->id}/grantAllBenefits", ['reason' => 'Granted again by mistake']);

            // Assert
            $this->assertSame(1, PatreonUserLink::query()->where('user_id', $user->id)->count());
            $this->assertSame(count(PatreonBenefit::ALL), PatreonUserBenefit::query()
                ->where('patreon_user_link_id', $user->refresh()->patreon_user_link_id)
                ->count());
            $this->assertSame(1, PatreonManualGrant::query()->active()->where('user_id', $user->id)->count());
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function grantAllBenefits_asNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->be($user);

        try {
            // Act
            $response = $this->post("/admin/user/{$user->id}/grantAllBenefits", ['reason' => self::REASON]);

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseMissing('patreon_manual_grants', ['user_id' => $user->id]);
        } finally {
            $this->cleanUp($user);
        }
    }

    private function createRealPatreonLink(User $user, Carbon $expiresAt): PatreonUserLink
    {
        $patreonUserLink = PatreonUserLink::create([
            'user_id'       => $user->id,
            'email'         => $user->email,
            'scope'         => 'identity',
            'access_token'  => 'real-access-token',
            'refresh_token' => 'real-refresh-token',
            'version'       => 2,
            'expires_at'    => $expiresAt->toDateTimeString(),
        ]);

        $user->update(['patreon_user_link_id' => $patreonUserLink->id]);

        return $patreonUserLink;
    }

    private function cleanUp(User $user): void
    {
        $patreonUserLink = PatreonUserLink::query()->where('user_id', $user->id)->first();

        if ($patreonUserLink !== null) {
            PatreonUserBenefit::query()->where('patreon_user_link_id', $patreonUserLink->id)->delete();
            User::query()->whereKey($user->id)->update(['patreon_user_link_id' => null]);
            $patreonUserLink->delete();
        }

        PatreonManualGrant::query()->where('user_id', $user->id)->delete();

        $user->delete();
    }
}
