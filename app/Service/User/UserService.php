<?php

namespace App\Service\User;

use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService implements UserServiceInterface
{
    use AuthenticatesUsers;

    private const CACHE_KEY_USER_AUTH = 'user_auth:%s-%s';
    private const CACHE_TTL_USER_AUTH = 300;

    public function __construct(
        private readonly CacheServiceInterface $cacheService,
    ) {
    }

    public function loginAsUserFromAuthenticationHeader(Request $request): bool
    {
        if (!$request->hasHeader('Authorization')) {
            return false;
        }

        $authentication = $request->header('Authorization');
        if (!Str::startsWith($authentication, 'Basic')) {
            return false;
        }

        $base64     = Str::replace('Basic ', '', $authentication);
        $usernamePw = base64_decode($base64);
        if ($usernamePw === false) {
            return false;
        }

        $explode = explode(':', $usernamePw);
        if (count($explode) !== 2) {
            return false;
        }

        [
            $username,
            $password,
        ] = $explode;

        return $this->loginAsUser($username, $password);
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
