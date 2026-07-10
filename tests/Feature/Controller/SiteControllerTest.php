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

    #[Test]
    public function index_givenWorktreeConfigured_rendersWorktreeNameInFooter(): void
    {
        // Arrange
        config(['keystoneguru.worktree' => '1234-some-worktree']);

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertSee('1234-some-worktree');
    }

    #[Test]
    public function index_givenNoWorktreeConfigured_doesNotRenderWorktreeFooterElement(): void
    {
        // Arrange
        config(['keystoneguru.worktree' => null]);

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertDontSee('site-footer__worktree');
    }
}
