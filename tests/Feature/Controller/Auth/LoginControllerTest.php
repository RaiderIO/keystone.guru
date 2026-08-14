<?php

namespace Tests\Feature\Controller\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Auth')]
final class LoginControllerTest extends PublicTestCase
{
    #[Test]
    public function login_givenInvalidCredentials_redirectsToLoginWithErrors(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->post(route('login'), [
                'email'    => $user->email,
                'password' => 'definitely-not-the-password',
            ]);

            // Assert
            $response->assertRedirect(route('login', ['redirect' => '/']));
            $response->assertSessionHasErrors(['email']);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function login_givenInvalidCredentialsExpectingJson_returnsUnprocessableWithFieldErrors(): void
    {
        // Arrange - the modal form submits with Accept: application/json so a failure renders
        // inside the modal instead of redirecting the page (issue #4003)
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->postJson(route('login'), [
                'email'    => $user->email,
                'password' => 'definitely-not-the-password',
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['email']);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function login_givenValidCredentials_authenticatesAndRedirects(): void
    {
        // Arrange - the factory hashes the literal string 'password'
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->post(route('login'), [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            // Assert
            $response->assertRedirect();
            $this->assertAuthenticatedAs($user);
        } finally {
            auth()->logout();
            $user->delete();
        }
    }

    #[Test]
    public function showLoginForm_givenFailedLogin_rendersFieldErrorMarkup(): void
    {
        // Arrange - fail a login so the session carries errors for the follow-up page render
        $user = User::factory()->create();

        try {
            $this->post(route('login'), [
                'email'    => $user->email,
                'password' => 'definitely-not-the-password',
            ]);

            // Act
            $response = $this->get(route('login', ['redirect' => '/']));

            // Assert - the email field is marked invalid and carries a visible field-level message
            $response->assertOk();
            $response->assertSee('form-control is-invalid', false);
            $response->assertSee('aria-invalid="true"', false);
            $response->assertSee('invalid-feedback', false);
        } finally {
            $user->delete();
        }
    }
}
