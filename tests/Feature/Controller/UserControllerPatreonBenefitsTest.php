<?php

namespace Tests\Feature\Controller;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Admin')]
#[Group('Patreon')]
final class UserControllerPatreonBenefitsTest extends PublicTestCase
{
    private const array AJAX_HEADERS = [
        'X-Requested-With' => 'XMLHttpRequest',
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    private function createPatreonLinkedUser(): User
    {
        $user = User::factory()->create();

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

        return $user->refresh();
    }

    #[Test]
    public function storePatreonBenefits_givenPatreonLinkedUser_updatesBenefitsAndReturnsNoContent(): void
    {
        // Arrange - this is the URL the admin user list page's Patreon benefits select PUTs to;
        // it must match the route in routes/web.php exactly (issue #4372: it drifted to a
        // singular 'benefit' after the route was renamed from 'paidtier' to 'benefits').
        $user = $this->createPatreonLinkedUser();

        try {
            // Act
            $response = $this->put("/ajax/user/{$user->id}/patreon/benefits", [
                'patreonBenefits' => [PatreonBenefit::ALL[PatreonBenefit::AD_FREE]],
            ], self::AJAX_HEADERS);

            // Assert
            $response->assertNoContent();

            $this->assertTrue(
                $user->patreonUserLink->patreonBenefits()->where('patreon_benefits.id', PatreonBenefit::ALL[PatreonBenefit::AD_FREE])->exists(),
            );
        } finally {
            $user->patreonUserLink()->first()?->delete();
            $user->delete();
        }
    }

    #[Test]
    public function storePatreonBenefits_givenUserWithoutPatreonLink_returnsBadRequest(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->put("/ajax/user/{$user->id}/patreon/benefits", [
                'patreonBenefits' => [PatreonBenefit::ALL[PatreonBenefit::AD_FREE]],
            ], self::AJAX_HEADERS);

            // Assert
            $response->assertBadRequest();
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function storePatreonBenefits_asNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->be($user);

        try {
            // Act
            $response = $this->put("/ajax/user/{$user->id}/patreon/benefits", [
                'patreonBenefits' => [],
            ], self::AJAX_HEADERS);

            // Assert
            $response->assertForbidden();
        } finally {
            $user->delete();
        }
    }
}
