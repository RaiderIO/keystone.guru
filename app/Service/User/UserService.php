<?php

namespace App\Service\User;

use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\User\Dtos\BasicAuthenticationResult;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService implements UserServiceInterface
{
    use AuthenticatesUsers;

    private const string CACHE_KEY_USER_AUTH = 'user_auth:%s-%s';
    private const int CACHE_TTL_USER_AUTH    = 300;

    public function __construct(
        private readonly CacheServiceInterface $cacheService,
    ) {
    }

    public function loginAsUserFromAuthenticationHeader(Request $request): BasicAuthenticationResult
    {
        if (!$request->hasHeader('Authorization')) {
            return BasicAuthenticationResult::MissingHeader;
        }

        $authentication = (string)$request->header('Authorization');
        if (!Str::startsWith($authentication, 'Basic')) {
            return BasicAuthenticationResult::UnsupportedScheme;
        }

        $base64     = Str::replace('Basic ', '', $authentication);
        $usernamePw = base64_decode($base64);
        if ($usernamePw === false) { // @phpstan-ignore identical.alwaysFalse
            return BasicAuthenticationResult::MalformedCredentials;
        }

        // RFC 7617 forbids a colon in the userid but explicitly allows one in the password, so only
        // the first colon separates the two - splitting on every colon made any password containing
        // one impossible to authenticate with, while it kept working through the login form.
        $explode = explode(':', $usernamePw, 2);
        if (count($explode) !== 2) {
            return BasicAuthenticationResult::MalformedCredentials;
        }

        [
            $username,
            $password,
        ] = $explode;

        // Guzzle sends `Basic Og==` (an empty username and password) when it is handed null credentials,
        // which would otherwise reach the database as a lookup for the user with an empty email address
        if ($username === '' || $password === '') {
            return BasicAuthenticationResult::MalformedCredentials;
        }

        return $this->loginAsUser($username, $password)
            ? BasicAuthenticationResult::Success
            : BasicAuthenticationResult::CredentialsRejected;
    }

    /**
     * Logs in as a user with the given email and password. This uses caching to prevent expensive password hashing
     * for every single correct attempt.
     *
     * @param  string $email
     * @param  string $password
     * @return bool
     */
    public function loginAsUser(string $email, string $password): bool
    {
        // Use a more secure cache key (HMAC for password)
        $cacheKey = sprintf(
            self::CACHE_KEY_USER_AUTH,
            $email,
            hash_hmac('sha256', $password, (string)config('app.key')),
        );

        // Fast-path: Check cache for authenticated user
        if ($user = $this->cacheService->get($cacheKey)) {
            auth()->setUser($user);

            return true;
        }

        $user = User::where('email', $email)->first();

        // Perform the expensive password verification
        if (!$user || !Hash::check($password, $user->password)) {
            return false;
        }

        // Cache user for 5 minutes (only caches the user object, not the password)
        $this->cacheService->set($cacheKey, $user, self::CACHE_TTL_USER_AUTH);

        // Authenticate the user
        auth()->setUser($user);

        return true;
    }
}
