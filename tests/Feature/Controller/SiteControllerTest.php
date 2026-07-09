<?php

namespace Tests\Feature\Controller;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class SiteControllerTest extends PublicTestCase
{
    #[Test]
    public function index_givenGuest_returnsHomeLayout(): void
    {
        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('home.layout');
    }

    #[Test]
    public function index_givenAuthenticatedUser_returnsHomeLayout(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->actingAs($user)->get(route('home'));

            // Assert
            $response->assertOk();
            $response->assertViewIs('home.layout');
        } finally {
            $user->delete();
        }
    }
}
