<?php

namespace Tests\Feature\Controller;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Patreon')]
final class PatreonControllerTest extends PublicTestCase
{
    #[Test]
    public function link_givenNoCodeParameter_redirectsWithCancelledFlashAndDoesNotThrow(): void
    {
        // Arrange - Patreon omits `code` when the user denies the authorization prompt
        $user  = User::findOrFail(1);
        $state = 'test-csrf-token';

        // Act
        $response = $this->actingAs($user)
            ->withSession(['_token' => $state])
            ->get('/patreon-link?state=' . $state);

        // Assert
        $response->assertRedirect(route('profile.edit', ['#patreon']));
        $response->assertSessionHas('warning', __('controller.patreon.flash.link_cancelled'));
    }

    #[Test]
    public function link_givenBlankCodeParameter_redirectsWithCancelledFlash(): void
    {
        // Arrange
        $user  = User::findOrFail(1);
        $state = 'test-csrf-token';

        // Act
        $response = $this->actingAs($user)
            ->withSession(['_token' => $state])
            ->get('/patreon-link?state=' . $state . '&code=');

        // Assert
        $response->assertRedirect(route('profile.edit', ['#patreon']));
        $response->assertSessionHas('warning', __('controller.patreon.flash.link_cancelled'));
    }
}
