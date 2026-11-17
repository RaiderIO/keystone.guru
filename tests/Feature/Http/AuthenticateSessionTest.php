<?php

namespace Tests\Feature\Http;

use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('AuthenticateSession')]
final class AuthenticateSessionTest extends PublicTestCase
{
    private const string BROWSER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36';

    #[Test]
    public function handle_givenPasswordChangedFromAnotherSession_logsThatOtherSessionOut(): void
    {
        $user = null;

        try {
            // Arrange - the factory hashes the literal string 'password'
            $user          = $this->userWithUserRole();
            $otherSession  = $this->logInAndGetSessionId($user, 'password');
            $actingSession = $this->logInAndGetSessionId($user, 'password');

            // Act
            $this->asSession($actingSession)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ])->assertSessionHasNoErrors();
            $response = $this->asSession($otherSession)->get(route('profile.edit'));

            // Assert
            $response->assertRedirect(route('login'));
            $this->assertGuest();
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function handle_givenPasswordChangedFromThisSession_keepsThisSessionLoggedIn(): void
    {
        $user = null;

        try {
            // Arrange
            $user          = $this->userWithUserRole();
            $actingSession = $this->logInAndGetSessionId($user, 'password');

            // Act
            $this->asSession($actingSession)->patch(route('profile.changepassword'), [
                'current_password'     => 'password',
                'new_password'         => 'a-brand-new-password',
                'new_password-confirm' => 'a-brand-new-password',
            ])->assertSessionHasNoErrors();
            $response = $this->asSession($actingSession)->get(route('profile.edit'));

            // Assert
            $response->assertOk();
            $this->assertAuthenticatedAs($user);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function handle_givenPasswordResetByToken_logsTheExistingSessionOut(): void
    {
        $user = null;

        try {
            // Arrange
            $user         = $this->userWithUserRole();
            $otherSession = $this->logInAndGetSessionId($user, 'password');
            $token        = Password::broker()->createToken($user);

            // Act
            $this->asSession(null)->post(route('password.update'), [
                'token'                 => $token,
                'email'                 => $user->email,
                'password'              => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])->assertSessionHasNoErrors();
            $response = $this->asSession($otherSession)->get(route('profile.edit'));

            // Assert
            $response->assertRedirect(route('login'));
            $this->assertGuest();
        } finally {
            if ($user !== null) {
                DB::table((string)config('auth.passwords.users.table'))->where('email', $user->email)->delete();
                $user->delete();
            }
        }
    }

    #[Test]
    public function handle_givenUnchangedPassword_keepsAnotherSessionLoggedIn(): void
    {
        $user = null;

        try {
            // Arrange
            $user         = $this->userWithUserRole();
            $otherSession = $this->logInAndGetSessionId($user, 'password');
            $this->logInAndGetSessionId($user, 'password');

            // Act
            $response = $this->asSession($otherSession)->get(route('profile.edit'));

            // Assert
            $response->assertOk();
            $this->assertAuthenticatedAs($user);
        } finally {
            $user?->delete();
        }
    }

    /**
     * Signs in through the login form as a browser would, and returns the id of the session that sign-in opened.
     */
    private function logInAndGetSessionId(User $user, string $password): string
    {
        $response = $this->asSession(null)->post(route('login'), [
            'email'    => $user->email,
            'password' => $password,
        ]);
        $response->assertSessionHasNoErrors();

        return $this->sessionIdOf($response);
    }

    /**
     * Makes the next request arrive as a fresh browser request carrying only the given session's cookie: the test
     * application keeps the resolved guard and the in-memory session attributes between requests otherwise.
     */
    private function asSession(?string $sessionId): self
    {
        Auth::forgetGuards();
        $this->app['session']->driver()->flush();
        $this->flushSession();
        $this->defaultCookies = [];

        $this->withHeaders(['User-Agent' => self::BROWSER_USER_AGENT]);
        if ($sessionId !== null) {
            $this->withCookie((string)config('session.cookie'), $sessionId);
        }

        return $this;
    }

    private function userWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    /**
     * @param TestResponse<Response> $response
     */
    private function sessionIdOf(TestResponse $response): string
    {
        $cookie = $response->getCookie((string)config('session.cookie'));
        $this->assertNotNull($cookie);

        return (string)$cookie->getValue();
    }
}
