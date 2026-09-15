<?php

namespace Tests\Feature\Controller\Auth;

use App\Email\CustomPasswordResetEmail;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

#[Group('Auth')]
#[Group('RateLimiting')]
final class ForgotPasswordControllerTest extends PublicTestCase
{
    #[Test]
    public function sendResetLinkEmail_givenUnknownEmail_answersTheSameAsAKnownEmail(): void
    {
        // Arrange
        Notification::fake();
        $user = User::factory()->create();

        try {
            // Act
            $knownResponse   = $this->post(route('password.email'), ['email' => $user->email]);
            $unknownResponse = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

            // Assert
            $knownResponse->assertStatus(302);
            $unknownResponse->assertStatus(302);
            $knownResponse->assertSessionHasNoErrors();
            $unknownResponse->assertSessionHasNoErrors();
            $this->assertSame(
                $knownResponse->headers->get('Location'),
                $unknownResponse->headers->get('Location'),
            );
            $knownResponse->assertSessionHas('status', __('passwords.sent'));
            $unknownResponse->assertSessionHas('status', __('passwords.sent'));
            // The uniform response must not have cost the known address its reset link
            Notification::assertSentTo($user, CustomPasswordResetEmail::class);
            Notification::assertCount(1);
        } finally {
            DB::table((string)config('auth.passwords.users.table'))->where('email', $user->email)->delete();
            $user->delete();
        }
    }

    #[Test]
    public function sendResetLinkEmail_givenTooManyRequests_isRateLimited(): void
    {
        // Arrange - one request per hour is enough to prove the throttle is applied to the route
        Notification::fake();
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $firstResponse  = $this->post(route('password.email'), ['email' => 'nobody@example.com']);
            $secondResponse = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

            // Assert
            $firstResponse->assertStatus(302);
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    #[Test]
    public function showLinkRequestForm_givenGuest_isNotRateLimited(): void
    {
        // Arrange
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $this->get(route('password.request'));
            $response = $this->get(route('password.request'));

            // Assert
            $response->assertStatus(200);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
