<?php

namespace Tests\Feature\Controller\Ajax;

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
        $response = $this->post('/ajax/profile/legal', ['time' => 123], self::AJAX_HEADERS);

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
            $response = $this->post('/ajax/profile/legal', ['time' => 123], self::AJAX_HEADERS);

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
}
