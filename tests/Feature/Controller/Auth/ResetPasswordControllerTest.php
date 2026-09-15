<?php

namespace Tests\Feature\Controller\Auth;

use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

#[Group('Auth')]
#[Group('RateLimiting')]
final class ResetPasswordControllerTest extends PublicTestCase
{
    #[Test]
    public function reset_givenTooManyRequests_isRateLimited(): void
    {
        // Arrange - one request per hour is enough to prove the throttle is applied to the route; the token is bogus,
        // so the first request is answered by the broker and never touches a user
        $this->overrideHttpRateLimit(1);
        $payload = [
            'token'                 => 'some-token',
            'email'                 => 'nobody@example.com',
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ];

        try {
            // Act
            $firstResponse  = $this->post(route('password.update'), $payload);
            $secondResponse = $this->post(route('password.update'), $payload);

            // Assert
            $firstResponse->assertStatus(302);
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    #[Test]
    public function showResetForm_givenGuest_isNotRateLimited(): void
    {
        // Arrange
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $this->get(route('password.reset', ['token' => 'some-token']));
            $response = $this->get(route('password.reset', ['token' => 'some-token']));

            // Assert
            $response->assertStatus(200);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

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

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
