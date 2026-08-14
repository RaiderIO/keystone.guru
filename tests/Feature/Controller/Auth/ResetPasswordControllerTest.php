<?php

namespace Tests\Feature\Controller\Auth;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Auth')]
final class ResetPasswordControllerTest extends PublicTestCase
{
    #[Test]
    public function showResetForm_givenEmailQueryParam_prefillsTheEmailField(): void
    {
        // Arrange - the legacy Blade `{{ $email or old('email') }}` compiled to a boolean and
        // rendered the literal value "1" in this field (issue #4003)
        $email = 'someone@example.com';

        // Act
        $response = $this->get(route('password.reset', ['token' => 'some-token', 'email' => $email]));

        // Assert - scoped to the email input since other inputs on the page legitimately carry value="1"
        $response->assertOk();
        $response->assertSee(sprintf('name="email" value="%s"', $email), false);
    }

    #[Test]
    public function showResetForm_givenNoEmailQueryParam_rendersAnEmptyEmailField(): void
    {
        // Act
        $response = $this->get(route('password.reset', ['token' => 'some-token']));

        // Assert
        $response->assertOk();
        $response->assertSee('name="email" value=""', false);
    }

    #[Test]
    public function showResetForm_givenGuest_postsToThePasswordUpdateRoute(): void
    {
        // Act
        $response = $this->get(route('password.reset', ['token' => 'some-token']));

        // Assert - the form used to point at route('password.request') and only worked because
        // that GET route shares its path with the password.update POST route
        $response->assertOk();
        $response->assertSee(sprintf('action="%s"', route('password.update')), false);
    }
}
