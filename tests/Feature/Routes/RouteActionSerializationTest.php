<?php

namespace Tests\Feature\Routes;

use Closure;
use Illuminate\Support\Facades\Route;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionFunction;
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

    /**
     * The same `route:cache` round-trip decides whether `$this` survives, and it silently does not
     * for some methods. `Native::__serialize()` only captures the bound object when
     * `ReflectionClosure::isBindingRequired()` is true, and that flag is set by the tokenizer only
     * for `$this` tokens appearing *outside* any nested closure literal. So a controller method
     * whose only `$this` usage sits inside a nested closure - e.g. a body wrapped in
     * `DB::transaction(function () use (...) { ... $this->foo(); ... })` - is reconstructed
     * unbound, and the first `$this->` inside that closure throws
     * `Error: Using $this when not in object context`.
     *
     * That fails per-request in production only: routes aren't cached under runningUnitTests(), so
     * an ordinary feature test exercises the un-serialized closure and passes either way. #4329 hit
     * this on the map icon, path, arrow and brushline delete endpoints (plus six admin mapping
     * editor deletes that had been broken since 2023 without a Sentry signal, because the mapping
     * is authored locally and shipped through the seeders).
     *
     * Adding a top-level `fn () => $this->foo()` does not help - any closure *literal* in the
     * eval'd body is compiled at file scope. Only a genuine `$this` read outside a closure sets the
     * flag, which is why the fix is to delegate to a real private method.
     */
    #[Test]
    public function routes_givenActionUsingThisOnlyInsideNestedClosure_remainBoundAfterRoundTrip(): void
    {
        // Arrange/Act
        $failures = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uses = $route->getAction('uses');
            if (!$uses instanceof Closure) {
                continue;
            }

            // Only actions that actually read $this can break; a comment mentioning it cannot,
            // hence the tokenizer rather than a string search.
            if (!$this->readsThis($uses)) {
                continue;
            }

            $reconstructed = unserialize(serialize(new SerializableClosure($uses)))->getClosure();

            if ((new ReflectionFunction($reconstructed))->getClosureThis() !== null) {
                continue;
            }

            $reflection = new ReflectionFunction($uses);
            $failures[] = sprintf(
                '%s %s (%s:%d)',
                implode('|', $route->methods()),
                $route->uri(),
                $reflection->getFileName(),
                $reflection->getStartLine(),
            );
        }

        // Assert
        $this->assertSame(
            [],
            $failures,
            "The following route actions read \$this but lose their binding in the SerializableClosure "
            . "round-trip route:cache performs in production, so they throw 'Using \$this when not in "
            . "object context' per request. Delegate the body to a private method so the route-registered "
            . "method itself reads \$this outside any closure:\n" . implode("\n", $failures),
        );
    }

    /**
     * Whether the closure's source contains a real `$this` variable token, ignoring comments and
     * strings that merely mention it.
     */
    private function readsThis(Closure $closure): bool
    {
        $reflection = new ReflectionFunction($closure);
        $file       = $reflection->getFileName();

        if ($file === false || !is_file($file)) {
            return false;
        }

        $lines  = file($file) ?: [];
        $source = implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1,
        ));

        foreach (token_get_all('<?php ' . $source) as $token) {
            if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$this') {
                return true;
            }
        }

        return false;
    }
}
