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
