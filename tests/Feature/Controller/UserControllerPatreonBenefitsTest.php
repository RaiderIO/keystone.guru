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

    #[Test]
    public function getPatreonBenefits_givenUserHydratedInACollection_readsTheEagerLoadedRelation(): void
    {
        // Arrange - method_exists() is case insensitive, so reading the relation under a casing that does not
        // match the one PatreonUserLink::$with eager-loads still returns the right benefits (#4386)
        $user = $this->createPatreonLinkedUser();
        $user->patreonUserLink->patreonBenefits()->attach(PatreonBenefit::ALL[PatreonBenefit::AD_FREE]);

        try {
            // Act
            $users                = User::query()->whereKey($user->id)->with(['patreonUserLink'])->get();
            $patreonUserLink      = $users->first()->patreonUserLink;
            $eagerLoadedRelations = array_keys($patreonUserLink->getRelations());

            $result = $users->first()->getPatreonBenefits();

            // Assert
            $this->assertSame([PatreonBenefit::AD_FREE], $result->values()->all());
            $this->assertTrue($users->first()->hasPatreonBenefit(PatreonBenefit::AD_FREE));

            $this->assertSame(
                $eagerLoadedRelations,
                array_keys($patreonUserLink->getRelations()),
                'getPatreonBenefits() loaded a second copy of the benefits instead of using the relation PatreonUserLink::$with already loaded',
            );
        } finally {
            $user->patreonUserLink()->first()?->delete();
            $user->delete();
        }
    }

    #[Test]
    public function getUsers_givenPatreonLinkedUser_serialisesBenefitsUnderTheKeyTheAdminTableReads(): void
    {
        // Arrange - the admin users table renders its Patreon column from these keys; a key it does not
        // recognise empties the column without any error (#4386)
        $user = $this->createPatreonLinkedUser();
        $user->patreonUserLink->patreonBenefits()->attach(PatreonBenefit::ALL[PatreonBenefit::AD_FREE]);

        try {
            // Act
            $response = $this->get(sprintf('/ajax/admin/user?%s', http_build_query([
                'draw'    => 1,
                'start'   => 0,
                'length'  => 10,
                'columns' => [
                    [
                        'name'       => 'email',
                        'searchable' => 'true',
                        'orderable'  => 'false',
                        'search'     => ['value' => $user->email],
                    ],
                ],
            ])), self::AJAX_HEADERS);

            // Assert
            $response->assertOk();

            /** @var array<int, array<string, mixed>> $data */
            $data = $response->json('data');
            $rows = array_values(array_filter($data, fn(array $row): bool => $row['id'] === $user->id));

            $this->assertCount(1, $rows);
            $this->assertArrayHasKey('manually_granted', $rows[0]['patreon_user_link']);

            $this->assertSame(
                [PatreonBenefit::ALL[PatreonBenefit::AD_FREE]],
                array_column($rows[0]['patreon_user_link']['patreon_benefits'], 'id'),
            );
        } finally {
            $user->patreonUserLink()->first()?->delete();
            $user->delete();
        }
    }
}
