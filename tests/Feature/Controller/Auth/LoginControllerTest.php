<?php

namespace Tests\Feature\Controller\Auth;

use App\Models\User;
use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

#[Group('Auth')]
#[Group('RateLimiting')]
final class LoginControllerTest extends PublicTestCase
{
    #[Test]
    public function login_givenTooManyRequests_isRateLimited(): void
    {
        // Arrange - one request per hour is enough to prove the throttle is applied to the route; the credentials
        // are wrong on purpose, so the per-username lockout of ThrottlesLogins is not what answers the second request
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $firstResponse  = $this->post(route('login'), ['email' => 'nobody@example.com', 'password' => 'definitely-not-the-password']);
            $secondResponse = $this->post(route('login'), ['email' => 'somebody-else@example.com', 'password' => 'definitely-not-the-password']);

            // Assert
            $firstResponse->assertStatus(302);
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    #[Test]
    public function showLoginForm_givenGuest_isNotRateLimited(): void
    {
        // Arrange
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $this->get(route('login'));
            $response = $this->get(route('login'));

            // Assert
            $response->assertStatus(200);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

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

    #[Test]
    public function login_givenRelativeRedirect_redirectsToThatPath(): void
    {
        // Arrange - the factory hashes the literal string 'password'
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->post(route('login', ['redirect' => '/routes']), [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            // Assert
            $response->assertRedirect('/routes');
        } finally {
            auth()->logout();
            $user->delete();
        }
    }

    #[Test]
    #[DataProvider('offSiteRedirect_dataProvider')]
    public function login_givenOffSiteRedirect_redirectsToTheDefault(string $redirect): void
    {
        // Arrange - the factory hashes the literal string 'password'
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->post(route('login', ['redirect' => $redirect]), [
                'email'    => $user->email,
                'password' => 'password',
            ]);

            // Assert
            $response->assertRedirect('/');
        } finally {
            auth()->logout();
            $user->delete();
        }
    }

    #[Test]
    #[DataProvider('offSiteRedirect_dataProvider')]
    public function login_givenOffSiteRedirectAndInvalidCredentials_redirectsBackToTheDefault(string $redirect): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->post(route('login', ['redirect' => $redirect]), [
                'email'    => $user->email,
                'password' => 'definitely-not-the-password',
            ]);

            // Assert
            $response->assertRedirect(route('login', ['redirect' => '/']));
        } finally {
            $user->delete();
        }
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function offSiteRedirect_dataProvider(): array
    {
        return [
            'absolute'          => ['https://google.com/test'],
            'protocol relative' => ['//google.com/test'],
            'backslashes'       => ['\\\\google.com/test'],
            'scheme only'       => ['javascript:doSomething()'],
            'no leading slash'  => ['google.com/test'],
        ];
    }
}
