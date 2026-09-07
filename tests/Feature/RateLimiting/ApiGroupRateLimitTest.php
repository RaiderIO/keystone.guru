<?php

namespace Tests\Feature\RateLimiting;

use App\Http\Middleware\Api\ApiAuthentication;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('RateLimiting')]
#[Group('Api')]
final class ApiGroupRateLimitTest extends PublicTestCase
{
    private const string PASSWORD = 'a-password-for-this-test';

    #[Test]
    public function apiMiddlewareGroup_givenTheStackTheRouterExecutes_runsTheLimiterAfterAuthentication(): void
    {
        // Arrange - the middleware groups are registered while the http kernel handles a request, not on boot
        $this->get(route('api.v1.combatlog.dungeon.index'));
        $route = app('router')->getRoutes()->getByName('api.v1.combatlog.dungeon.index');

        // Act - gatherRouteMiddleware() returns the stack sorted by the priority list, which is what actually runs
        $middleware = array_values(app('router')->gatherRouteMiddleware($route));

        // Assert
        $throttleIndex = $this->indexOfMiddlewareContaining($middleware, 'api-general');

        $this->assertNotNull($throttleIndex, 'The api middleware group must throttle with the api-general limiter');
        $this->assertGreaterThan(
            array_search(ApiAuthentication::class, $middleware, true),
            $throttleIndex,
        );
    }

    #[Test]
    public function apiGeneralLimiter_givenAnonymousRequest_allowsTheConfiguredPerMinuteCeiling(): void
    {
        // Arrange
        $this->overrideApiRateLimit(null);

        // Act
        $limiter = app(RateLimiter::class)->limiter('api-general');
        /** @var Limit $limit */
        $limit = $limiter(Request::create('/api/v1/dungeon', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']));

        // Assert
        $this->assertSame(900, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
    }

    #[Test]
    public function apiRoute_givenTooManyRequests_isRateLimited(): void
    {
        // Arrange - one request per minute is enough to prove the group throttle is actually applied
        $this->overrideApiRateLimit(1);

        try {
            // Act
            $firstResponse  = $this->get(route('api.v1.combatlog.dungeon.index'));
            $secondResponse = $this->get(route('api.v1.combatlog.dungeon.index'));

            // Assert
            $firstResponse->assertStatus(StatusCode::OK);
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideApiRateLimit(null);
        }
    }

    #[Test]
    public function apiRoute_givenTwoCallersWithTheirOwnCredentials_givesThemTheirOwnBudget(): void
    {
        // Arrange - both callers share an IP, so a limiter running before authentication would put them
        // in one bucket. Authentication is only performed when the application does not know it is
        // running tests, so that is what makes the credentials count here.
        $firstCaller  = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $secondCaller = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $this->overrideApiRateLimit(1);
        $this->pretendNotRunningUnitTests();

        try {
            // Act
            $firstCallerResponse       = $this->get(route('api.v1.combatlog.dungeon.index'), $this->credentialsOf($firstCaller));
            $secondCallerResponse      = $this->get(route('api.v1.combatlog.dungeon.index'), $this->credentialsOf($secondCaller));
            $firstCallerSecondResponse = $this->get(route('api.v1.combatlog.dungeon.index'), $this->credentialsOf($firstCaller));

            // Assert
            $firstCallerResponse->assertStatus(StatusCode::OK);
            $secondCallerResponse->assertStatus(StatusCode::OK);
            $firstCallerSecondResponse->assertStatus(429);
        } finally {
            $this->pretendRunningUnitTests();
            $this->overrideApiRateLimit(null);
            $secondCaller->delete();
            $firstCaller->delete();
        }
    }

    #[Test]
    public function apiRoute_givenAnAdministrator_keepsTheExemption(): void
    {
        // Arrange
        $administrator = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $administrator->addRole(Role::firstWhere('name', Role::ROLE_ADMIN));
        $this->overrideApiRateLimit(1);
        $this->pretendNotRunningUnitTests();

        try {
            // Act
            $firstResponse  = $this->get(route('api.v1.combatlog.dungeon.index'), $this->credentialsOf($administrator));
            $secondResponse = $this->get(route('api.v1.combatlog.dungeon.index'), $this->credentialsOf($administrator));

            // Assert - the exemption only applies when the limiter can see who is calling
            $firstResponse->assertStatus(StatusCode::OK);
            $secondResponse->assertStatus(StatusCode::OK);
        } finally {
            $this->pretendRunningUnitTests();
            $this->overrideApiRateLimit(null);
            $administrator->removeRole(Role::firstWhere('name', Role::ROLE_ADMIN));
            $administrator->delete();
        }
    }

    /**
     * @return array<string, string>
     */
    private function credentialsOf(User $user): array
    {
        return ['Authorization' => sprintf('Basic %s', base64_encode(sprintf('%s:%s', $user->email, self::PASSWORD)))];
    }

    /**
     * @param array<int, string> $middleware
     */
    private function indexOfMiddlewareContaining(array $middleware, string $needle): ?int
    {
        foreach ($middleware as $index => $name) {
            if (str_contains($name, $needle)) {
                return $index;
            }
        }

        return null;
    }

    private function pretendNotRunningUnitTests(): void
    {
        $this->app['env'] = 'local';
    }

    private function pretendRunningUnitTests(): void
    {
        $this->app['env'] = 'testing';
    }

    private function overrideApiRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverridePerMinuteApi')->setValue(null, $limit);
    }
}
