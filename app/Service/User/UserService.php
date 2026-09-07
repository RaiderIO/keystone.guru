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
        if ($this->loginAsCachedUserFromAuthenticationHeader($request)) {
            return BasicAuthenticationResult::Success;
        }

        return $this->verifyUserFromAuthenticationHeader($request);
    }

    public function loginAsCachedUserFromAuthenticationHeader(Request $request): bool
    {
        $credentials = $this->credentialsFromAuthenticationHeader($request);

        if ($credentials instanceof BasicAuthenticationResult) {
            return false;
        }

        return $this->loginAsCachedUser(...$credentials);
    }

    public function verifyUserFromAuthenticationHeader(Request $request): BasicAuthenticationResult
    {
        $credentials = $this->credentialsFromAuthenticationHeader($request);

        if ($credentials instanceof BasicAuthenticationResult) {
            return $credentials;
        }

        return $this->verifyUser(...$credentials)
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
        // Fast-path: Check cache for authenticated user
        return $this->loginAsCachedUser($email, $password) || $this->verifyUser($email, $password);
    }

    /**
     * Compares the given password against the user's stored hash - the expensive half of loginAsUser(), split off
     * so a caller that has already established the credentials are not cached does not read the cache twice.
     */
    private function verifyUser(string $email, string $password): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return false;
        }

        // Cache user for 5 minutes (only caches the user object, not the password). The entry is deliberately not
        // renewed when it is read, so a password that changed is picked up within the TTL even by a caller that
        // never stops making requests.
        $this->cacheService->set($this->userAuthCacheKey($email, $password), $user, self::CACHE_TTL_USER_AUTH);

        // Authenticate the user
        auth()->setUser($user);

        return true;
    }

    /**
     * @return array{0: string, 1: string}|BasicAuthenticationResult The credentials, or why they could not be read
     */
    private function credentialsFromAuthenticationHeader(Request $request): array|BasicAuthenticationResult
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

        return [
            $username,
            $password,
        ];
    }

    /**
     * Authenticates the given credentials only if a previous request already verified them - it costs a cache read
     * and never a password hash comparison.
     */
    private function loginAsCachedUser(string $email, string $password): bool
    {
        $user = $this->cacheService->get($this->userAuthCacheKey($email, $password));

        if (!$user) {
            return false;
        }

        auth()->setUser($user);

        return true;
    }

    /**
     * The password is only ever present as an HMAC, so the key cannot be walked back to it.
     */
    private function userAuthCacheKey(string $email, string $password): string
    {
        return sprintf(
            self::CACHE_KEY_USER_AUTH,
            $email,
            hash_hmac('sha256', $password, (string)config('app.key')),
        );
    }
}
