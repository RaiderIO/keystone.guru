<?php

namespace Tests\Feature\RateLimiting;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Each ceiling stays at or above the whole site's busiest hour, so only abuse reaches it, even behind
 * an address shared by many visitors.
 */
#[Group('RateLimiting')]
final class HttpRateLimitCeilingTest extends TestCase
{
    #[Test]
    #[DataProvider('limiterCeilingProvider')]
    public function limiter_givenAnonymousPost_allowsTheHourlyCeiling(string $limiterName, int $expectedMaxAttempts): void
    {
        // Arrange
        $this->overrideHttpRateLimit(null);

        try {
            // Act
            $limit = $this->resolveLimit($limiterName);

            // Assert
            $this->assertSame($expectedMaxAttempts, $limit->maxAttempts);
            $this->assertSame(3600, $limit->decaySeconds);
            $this->assertSame('203.0.113.10', $limit->key);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function limiterCeilingProvider(): array
    {
        return [
            'create-dungeonroute'  => ['create-dungeonroute', 100],
            'delete-dungeonroutes' => ['delete-dungeonroutes', 60],
            'edit-dungeonroute'    => ['edit-dungeonroute', 1200],
            'create-tag'           => ['create-tag', 60],
            'create-collection'    => ['create-collection', 30],
            'create-team'          => ['create-team', 5],
            'create-reports'       => ['create-reports', 60],
            'create-user'          => ['create-user', 50],
            'login'                => ['login', 120],
            'reset-password'       => ['reset-password', 60],
            'store-metric'         => ['store-metric', 600],
            'search-dungeonroute'  => ['search-dungeonroute', 600],
            'heatmap-data'         => ['heatmap-data', 600],
            'mdt-details'          => ['mdt-details', 300],
            'mdt-export'           => ['mdt-export', 600],
            'simulate'             => ['simulate', 120],
        ];
    }

    #[Test]
    #[TestWith([Role::ROLE_INTERNAL_TEAM])]
    #[TestWith([Role::ROLE_AI_AGENT])]
    public function limiter_givenInternalUser_isUnlimited(string $role): void
    {
        // Arrange
        $this->overrideHttpRateLimit(null);
        $user = User::factory()->create();

        try {
            $user->addRole($role);

            // Act
            $limit = $this->resolveLimit('edit-dungeonroute', $user);

            // Assert
            $this->assertInstanceOf(Unlimited::class, $limit);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function limiter_givenRegularUser_isLimitedAndKeyedByTheirUserId(): void
    {
        // Arrange
        $this->overrideHttpRateLimit(null);
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_USER);

            // Act
            $limit = $this->resolveLimit('edit-dungeonroute', $user);

            // Assert
            $this->assertNotInstanceOf(Unlimited::class, $limit);
            $this->assertSame(1200, $limit->maxAttempts);
            $this->assertSame((string)$user->id, $limit->key);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    #[TestWith(['create-user'])]
    #[TestWith(['reset-password'])]
    public function limiter_givenGetRequestForAnAccountForm_isUnlimited(string $limiterName): void
    {
        // Arrange
        $this->overrideHttpRateLimit(null);
        $limiter = app(RateLimiter::class)->limiter($limiterName);

        // Act
        $getLimit  = $limiter(Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']));
        $postLimit = $limiter(Request::create('/', 'POST', server: ['REMOTE_ADDR' => '203.0.113.10']));

        // Assert
        $this->assertInstanceOf(Unlimited::class, $getLimit);
        $this->assertNotInstanceOf(Unlimited::class, $postLimit);
    }

    private function resolveLimit(string $name, ?User $user = null): Limit
    {
        $limiter = app(RateLimiter::class)->limiter($name);
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $request->setUserResolver(static fn() => $user);

        return $limiter($request);
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
