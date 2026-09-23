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

    /** Versioned so that workers expecting a different payload shape never read each other's entries */
    private const string CACHE_KEY_USER_AUTH = 'user_auth_v2:%s-%s';
    private const int CACHE_TTL_USER_AUTH    = 300;

    public function __construct(
        private readonly CacheServiceInterface $cacheService,
    ) {
    }

    public function loginAsUserFromAuthenticationHeader(Request $request): BasicAuthenticationResult
    {
        $credentials = $this->credentialsFromAuthenticationHeader($request);

        if ($credentials instanceof BasicAuthenticationResult) {
            return $credentials;
        }

        return $this->loginAsUser(...$credentials)
            ? BasicAuthenticationResult::Success
            : BasicAuthenticationResult::CredentialsRejected;
    }

    public function hasVerifiedCredentialsCached(Request $request): bool
    {
        $credentials = $this->credentialsFromAuthenticationHeader($request);

        if ($credentials instanceof BasicAuthenticationResult) {
            return false;
        }

        return $this->findUserForCachedCredentials(...$credentials) !== null;
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
        // Fast-path: credentials verified recently, and the account has not changed since
        $user = $this->findUserForCachedCredentials($email, $password);
        if ($user !== null) {
            auth()->setUser($user);

            return true;
        }

        $user = User::where('email', $email)->first();

        // Perform the expensive password verification
        if (!$user || !Hash::check($password, $user->password)) {
            return false;
        }

        $this->cacheService->set(
            $this->userAuthCacheKey($email, $password),
            [
                'user_id'              => $user->id,
                'password_fingerprint' => $this->passwordFingerprint($user),
            ],
            self::CACHE_TTL_USER_AUTH,
        );

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
     * Resolves cached verified credentials to their user, but only while the account still exists under the
     * same email address with the same stored password hash - a password change or reset, or a deleted account,
     * invalidates the cached verification immediately rather than when it expires.
     */
    private function findUserForCachedCredentials(string $email, string $password): ?User
    {
        $cached = $this->cacheService->get($this->userAuthCacheKey($email, $password));
        if (!is_array($cached) || !isset($cached['user_id'], $cached['password_fingerprint'])) {
            return null;
        }

        // Compared in the database so the email matches under the same collation as the cold lookup
        /** @var User|null $user */
        $user = User::query()
            ->whereKey($cached['user_id'])
            ->where('email', $email)
            ->first();
        if ($user === null) {
            return null;
        }

        return hash_equals($this->passwordFingerprint($user), (string)$cached['password_fingerprint']) ? $user : null;
    }

    /**
     * Keeps the stored password hash itself out of the cache.
     */
    private function passwordFingerprint(User $user): string
    {
        return hash_hmac('sha256', (string)$user->password, (string)config('app.key'));
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
