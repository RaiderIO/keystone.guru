<?php

namespace Tests\Feature\Routes;

use Closure;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionFunction;
use Tests\TestCase;

#[Group('Routes')]
final class RouteActionSerializationTest extends TestCase
{
    /**
     * `php artisan route:cache` serializes a closure action, and Laravel 13's
     * `Route::runCallable()` restores it with an `allowed_classes` list that only admits the
     * serializable-closure classes. A first-class callable (`new XController()->method(...)`) is a
     * closure bound to the controller, so the controller comes back as `__PHP_Incomplete_Class` and
     * the first `$this->` in the method throws - per request, only once the route cache is warm,
     * never under runningUnitTests(). A `[XController::class, 'method']` action is cached as a plain
     * `Controller@method` string and does not go through unserialize at all.
     */
    #[Test]
    public function routes_givenControllerActions_areRegisteredAsControllerStringsNotBoundClosures(): void
    {
        // Arrange
        $routes = Route::getRoutes()->getRoutes();

        // Act
        $controllerStringCount = 0;
        $boundClosureRoutes    = [];

        foreach ($routes as $route) {
            $uses = $route->getAction('uses');

            if (is_string($uses) && str_starts_with($uses, 'App\\Http\\Controllers\\')) {
                ++$controllerStringCount;

                continue;
            }

            if (!$uses instanceof Closure) {
                continue;
            }

            $boundTo = (new ReflectionFunction($uses))->getClosureThis();
            if ($boundTo !== null && str_starts_with($boundTo::class, 'App\\Http\\Controllers\\')) {
                $boundClosureRoutes[] = sprintf('%s %s (%s)', implode('|', $route->methods()), $route->uri(), $boundTo::class);
            }
        }

        // Assert
        $this->assertGreaterThan(0, $controllerStringCount, 'No route is registered as a controller string');
        $this->assertSame(
            [],
            $boundClosureRoutes,
            "These routes are registered as a closure bound to a controller, which route:cache restores as an incomplete object. "
            . "Register them as [XController::class, 'method'] instead:\n" . implode("\n", $boundClosureRoutes),
        );
    }
}
