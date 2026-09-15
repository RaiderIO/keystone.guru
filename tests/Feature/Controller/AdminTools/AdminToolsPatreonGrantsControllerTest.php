<?php

namespace Tests\Feature\Controller\AdminTools;

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
final class AdminToolsPatreonGrantsControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function index_givenAuditedAndLegacyGrants_listsBoth(): void
    {
        // Arrange - the legacy user is a grant made before grants were recorded: no audit row exists for
        // them, the only trace is the Patreon link the admin panel fabricated at the time
        $auditedUser = User::factory()->create();
        $this->createFabricatedPatreonLink($auditedUser);
        PatreonManualGrant::factory()->create([
            'user_id' => $auditedUser->id,
            'reason'  => 'Compensated for a broken checkout',
        ]);

        $legacyUser = User::factory()->create();
        $this->createFabricatedPatreonLink($legacyUser);

        try {
            // Act
            $response = $this->get(route('admin.tools.patreon.grants.view'));

            // Assert
            $response->assertOk();
            $response->assertSee($auditedUser->name);
            $response->assertSee('Compensated for a broken checkout');
            $response->assertSee($legacyUser->name);
            $response->assertSee(__('view_admin.tools.patreon.grants.reason_unknown'));
        } finally {
            $this->cleanUp($auditedUser);
            $this->cleanUp($legacyUser);
        }
    }

    #[Test]
    public function index_asNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->be($user);

        try {
            // Act
            $response = $this->get(route('admin.tools.patreon.grants.view'));

            // Assert
            $response->assertForbidden();
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function revoke_givenALegacyDummyLinkWithNoGrantRow_removesTheLinkAndBenefits(): void
    {
        // Arrange - this is the population the page exists to clean up, and it has no grant row to bind
        // a route to, which is why revoking is keyed on the user instead
        $user            = User::factory()->create();
        $patreonUserLink = $this->createFabricatedPatreonLink($user);

        try {
            // Act
            $response = $this->delete(route('admin.tools.patreon.grants.revoke', ['user' => $user->id]));

            // Assert
            $response->assertRedirect(route('admin.tools.patreon.grants.view'));

            $this->assertDatabaseMissing('patreon_user_links', ['id' => $patreonUserLink->id]);
            $this->assertDatabaseMissing('patreon_user_benefits', ['patreon_user_link_id' => $patreonUserLink->id]);
            $this->assertNull($user->refresh()->patreon_user_link_id);
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function revoke_givenARealLinkedUser_removesOnlyTheGrantAndBenefits(): void
    {
        // Arrange - a real patron keeps their link: they are still a Patreon member, and the next
        // patreon:refreshmembers run puts the benefits of the tier they actually pay for back
        $user            = User::factory()->create();
        $patreonUserLink = PatreonUserLink::create([
            'user_id'       => $user->id,
            'email'         => $user->email,
            'scope'         => 'identity',
            'access_token'  => 'real-access-token',
            'refresh_token' => 'real-refresh-token',
            'version'       => 2,
            'expires_at'    => Carbon::now()->addDay()->toDateTimeString(),
        ]);
        $user->update(['patreon_user_link_id' => $patreonUserLink->id]);

        PatreonUserBenefit::create([
            'patreon_user_link_id' => $patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[PatreonBenefit::AD_FREE],
        ]);

        $grant = PatreonManualGrant::factory()->create(['user_id' => $user->id]);

        try {
            // Act
            $response = $this->delete(route('admin.tools.patreon.grants.revoke', ['user' => $user->id]));

            // Assert
            $response->assertRedirect(route('admin.tools.patreon.grants.view'));

            $this->assertDatabaseHas('patreon_user_links', ['id' => $patreonUserLink->id]);
            $this->assertDatabaseMissing('patreon_user_benefits', ['patreon_user_link_id' => $patreonUserLink->id]);

            $grant->refresh();
            $this->assertNotNull($grant->revoked_at);
            $this->assertSame(1, $grant->revoked_by_user_id);
        } finally {
            $this->cleanUp($user);
        }
    }

    #[Test]
    public function revoke_givenAUserWithoutAnyGrant_reportsThereIsNothingToRevoke(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->delete(route('admin.tools.patreon.grants.revoke', ['user' => $user->id]));

            // Assert
            $response->assertRedirect(route('admin.tools.patreon.grants.view'));
            $response->assertSessionHas('warning');
        } finally {
            $this->cleanUp($user);
        }
    }

    private function createFabricatedPatreonLink(User $user): PatreonUserLink
    {
        $patreonUserLink = PatreonUserLink::create([
            'user_id'       => $user->id,
            'email'         => $user->email,
            'scope'         => 'identity identity[email] identity.memberships campaigns',
            'access_token'  => PatreonUserLink::PERMANENT_TOKEN,
            'refresh_token' => PatreonUserLink::PERMANENT_TOKEN,
            'version'       => '0.0.1',
            'expires_at'    => Carbon::now()->addYears(100),
        ]);

        $user->update(['patreon_user_link_id' => $patreonUserLink->id]);

        PatreonUserBenefit::create([
            'patreon_user_link_id' => $patreonUserLink->id,
            'patreon_benefit_id'   => PatreonBenefit::ALL[PatreonBenefit::AD_FREE],
        ]);

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
