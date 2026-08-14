<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Profile')]
final class AjaxProfileControllerTest extends PublicTestCase
{
    private const array AJAX_HEADERS = [
        'X-Requested-With' => 'XMLHttpRequest',
    ];

    #[Test]
    public function legalAgree_givenGuest_returnsUnauthorizedInsteadOf500(): void
    {
        // Act - no actingAs(), request is unauthenticated
        $response = $this->post('/ajax/profile/legal', [], self::AJAX_HEADERS);

        // Assert
        $response->assertStatus(StatusCode::UNAUTHORIZED);
    }

    #[Test]
    public function legalAgree_givenAuthenticatedUserWithoutRole_isAllowedToAgree(): void
    {
        // Arrange - e.g. the seeded Internal Team account has no 'user'/'admin' role, but must
        // still be able to clear its own legal modal (it re-shows on every page until agreed)
        $roleLessUser = User::factory()->create();

        try {
            $this->actingAs($roleLessUser);

            // Act
            $response = $this->post('/ajax/profile/legal', [], self::AJAX_HEADERS);

            // Assert
            $response->assertNoContent();
            $this->assertSame(1, $roleLessUser->fresh()->legal_agreed);
        } finally {
            $roleLessUser->delete();
        }
    }

    #[Test]
    public function addAdFreeGiveaway_givenGuest_returnsUnauthorizedInsteadOf500(): void
    {
        // Arrange
        $target = User::factory()->create([
            'public_key' => User::generateRandomPublicKey(),
        ]);

        try {
            // Act - no actingAs(), request is unauthenticated
            $response = $this->post(sprintf('/ajax/profile/adfree/%s', $target->public_key), [], self::AJAX_HEADERS);

            // Assert
            $response->assertStatus(StatusCode::UNAUTHORIZED);
        } finally {
            $target->delete();
        }
    }

    #[Test]
    public function addAdFreeGiveaway_givenAdminGiver_returnsCreatedAndCreatesGiveaway(): void
    {
        // Arrange - the route requires role 'user' or 'admin', and PatreonAdFreeGiveaway::getCountLeft()
        // only allows giveaways for users with the AD_FREE_TEAM_MEMBERS patreon benefit, which
        // User::hasPatreonBenefit() grants automatically to admins
        $admin = User::factory()->create();
        $admin->addRole(Role::ROLE_ADMIN);

        $target = User::factory()->create([
            'public_key' => User::generateRandomPublicKey(),
        ]);

        try {
            $this->actingAs($admin);

            // Act
            $response = $this->post(sprintf('/ajax/profile/adfree/%s', $target->public_key), [], self::AJAX_HEADERS);

            // Assert - the controller returns the created PatreonAdFreeGiveaway model directly,
            // so Laravel auto-responds 201 Created rather than 200
            $response->assertCreated();
            $this->assertTrue(
                PatreonAdFreeGiveaway::query()
                    ->where('giver_user_id', $admin->id)
                    ->where('receiver_user_id', $target->id)
                    ->exists(),
            );
            $this->assertTrue($target->fresh()->hasAdFreeGiveaway());
        } finally {
            PatreonAdFreeGiveaway::query()->where('receiver_user_id', $target->id)->delete();
            $target->delete();
            $admin->delete();
        }
    }
}
