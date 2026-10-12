<?php

namespace Tests\Unit\App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

/**
 * Asserts on the origin check of the middleware as configured in bootstrap/app.php: Laravel skips request forgery
 * verification entirely under the testing environment (runningUnitTests()), so a feature POST would pass regardless.
 */
#[Group('Middleware')]
#[Group('RequestForgeryOrigin')]
class RequestForgeryOriginTest extends PublicTestCase
{
    #[Test]
    public function hasValidOrigin_givenSameSiteRequest_returnsFalse(): void
    {
        // Arrange
        $request = $this->createPostRequestFrom('same-site');

        // Act
        $result = $this->invokeHasValidOrigin($request);

        // Assert
        self::assertFalse($result);
    }

    #[Test]
    public function hasValidOrigin_givenSameOriginRequest_returnsTrue(): void
    {
        // Arrange
        $request = $this->createPostRequestFrom('same-origin');

        // Act
        $result = $this->invokeHasValidOrigin($request);

        // Assert
        self::assertTrue($result);
    }

    private function createPostRequestFrom(string $secFetchSite): Request
    {
        return Request::create('/ajax/some-other-post', 'POST', server: ['HTTP_SEC_FETCH_SITE' => $secFetchSite]);
    }

    private function invokeHasValidOrigin(Request $request): bool
    {
        $middleware = $this->app->make(PreventRequestForgery::class);
        $method     = new ReflectionMethod($middleware, 'hasValidOrigin');

        return $method->invoke($middleware, $request);
    }
}
