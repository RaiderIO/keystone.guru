<?php

namespace Tests\Feature\Routes;

use Closure;
use Illuminate\Support\Facades\Route;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

#[Group('Routes')]
final class RouteActionSerializationTest extends TestCase
{
    /**
     * `php artisan route:cache` serializes every closure-based route action via
     * laravel-serializable-closure, then reconstitutes it by `eval`-ing generated code on each
     * request once the cache is warm. A controller method literally named after a PHP reserved
     * word (`new`, `list`, ...) produces invalid generated code (`function new(...)`), which only
     * throws a ParseError at unserialize/eval time - not at serialize time, and never under
     * runningUnitTests() since routes aren't cached there. This has bitten `new`
     * (APIDungeonRouteDiscoverController) and `list` before; see the CLAUDE.md note on route
     * naming.
     *
     * This round-trips every registered closure route action through the exact same
     * serialize/unserialize cycle route:cache performs, so a reintroduced reserved-word method
     * name fails here deterministically instead of only surfacing per-request in production.
     */
    #[Test]
    public function routes_givenFirstClassCallableActions_surviveSerializableClosureRoundTrip(): void
    {
        // Arrange/Act
        $failures = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uses = $route->getAction('uses');
            if (!$uses instanceof Closure) {
                continue;
            }

            try {
                $serialized = serialize(new SerializableClosure($uses));
                unserialize($serialized)->getClosure();
            } catch (Throwable $e) {
                $failures[] = sprintf('%s %s: %s', implode('|', $route->methods()), $route->uri(), $e->getMessage());
            }
        }

        // Assert
        $this->assertSame(
            [],
            $failures,
            "The following routes failed the SerializableClosure round-trip route:cache performs in production:\n" . implode("\n", $failures),
        );
    }
}
