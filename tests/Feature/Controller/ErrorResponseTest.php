<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('ErrorResponse')]
final class ErrorResponseTest extends PublicTestCase
{
    #[Test]
    public function fallback_givenGetRequestToDeleteOnlyAjaxRoute_returns405Json(): void
    {
        // Act - /ajax/profile/adfree/{user} is registered for POST and DELETE, but not GET. It is an
        // API request (ApiRequestService), so no view composers run for it and this must never
        // render an HTML error view - it would crash on a missing view variable (#3806).
        $response = $this->get('/ajax/profile/adfree/1');

        // Assert
        $response->assertStatus(405);
        $response->assertHeader('Allow', 'POST, DELETE');
        $response->assertJson(['message' => 'Method Not Allowed']);
    }

    #[Test]
    public function fallback_givenJsonGetRequestToDeleteOnlyRoute_returns405Json(): void
    {
        // Act
        $response = $this->getJson('/ajax/profile/adfree/1');

        // Assert
        $response->assertStatus(405);
        $response->assertHeader('Allow', 'POST, DELETE');
        $response->assertJson(['message' => 'Method Not Allowed']);
    }

    #[Test]
    public function fallback_givenGetRequestToUnmatchedRoute_returns404HtmlPage(): void
    {
        // Act
        $response = $this->get('/admin/this-route-does-not-exist-xyz');

        // Assert
        $response->assertStatus(404);
        $response->assertSee(__('view_errors.404.title'));
    }

    #[Test]
    public function fallback_givenJsonGetRequestToUnmatchedRoute_returns404Json(): void
    {
        // Act
        $response = $this->getJson('/ajax/this/route/does/not/exist/xyz');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => __('exceptions.handler.api_route_not_found')]);
    }

    #[Test]
    public function fallback_givenPlainGetRequestToUnmatchedAjaxRoute_returns404Json(): void
    {
        // Act - a plain (non-JSON, non-XHR) request is exactly the crawler shape from #3806
        $response = $this->get('/ajax/this/route/does/not/exist/xyz');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => __('exceptions.handler.api_route_not_found')]);
    }

    #[Test]
    public function fallback_givenPlainGetRequestToBareApiPath_returns404Json(): void
    {
        // Act - "/api/" has no trailing segment, so it falls through to the public short-link
        // route (`{dungeonRoute}`, bound to "api") instead of any /api/* route, and 404s when that
        // model isn't found. The hand-rolled check the Handler used before ApiRequestService missed
        // this exact shape - it compared Request::decodedPath() (trailing slash trimmed to "api")
        // against 'api/', which never matches - so this rendered an HTML error view with every view
        // composer skipped, since ViewService keys composer registration off the raw, slash-
        // preserving path and does NOT run them for it (#3903).
        //
        // The standard $this->get()/call() test helpers can't reproduce this - Laravel's own
        // MakesHttpRequests::prepareUrlForRequest() always trims the trailing slash off the URI
        // before building the request, so "/api/" and "/api" are indistinguishable through them.
        // Dispatch a manually-built request through the kernel instead, matching what call() does
        // internally minus that trim, to keep the real trailing slash intact.
        $response = $this->dispatchWithRawPath('/api/');

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => __('exceptions.handler.api_model_not_found', [
            'ids'   => 'api',
            'model' => DungeonRoute::class,
        ])]);
    }

    #[Test]
    public function fallback_givenPlainGetRequestToBareApiPathWithoutTrailingSlash_returns404HtmlPage(): void
    {
        // Act - "/api" (no trailing slash) is a different shape from "/api/" above: its raw pathInfo
        // is "/api", which ApiRequestService does NOT consider an API request (only the
        // slash-suffixed '/api/' prefix is), and ViewService therefore runs composers for it. This
        // never crashed even before #3903, so it's the ordinary HTML 404 page rather than a
        // forced-JSON response - not a regression to fix, just the other half of the boundary the
        // fix above sits on.
        $response = $this->get('/api');

        // Assert
        $response->assertStatus(404);
        $response->assertSee(__('view_errors.404.title'));
    }

    #[Test]
    public function fallback_givenPlainWrongMethodRequestToViewRenderingAjaxSearchRoute_returns405Json(): void
    {
        // Act - '/ajax/search' is one of the handful of /ajax/ routes ViewService whitelists back
        // into loading view variables, because it renders an HTML fragment on success. That
        // whitelist is a view-layer concern only: the route is still an API request as far as
        // ApiRequestService is concerned, so its errors are still forced to JSON exactly as they
        // were before #3903 - same as the '/ajax/profile/adfree/1' case above.
        $response = $this->post('/ajax/search');

        // Assert - Allow also lists PATCH/DELETE: 'ajax/{dungeonRoute}' matches this exact URI too,
        // treating "search" as the dungeonRoute slug
        $response->assertStatus(405);
        $response->assertHeader('Allow', 'GET, HEAD, PATCH, DELETE');
        $response->assertJson(['message' => 'Method Not Allowed']);
    }

    #[Test]
    public function fallback_givenPlainPostRequestToGetOnlyBenchmarkRoute_returns405Json(): void
    {
        // Act - '/benchmark' is the third ApiRequestService prefix besides '/ajax/' and '/api/' -
        // the old hand-rolled check in the Handler never covered it at all, so any error there
        // (this route is GET|HEAD only) rendered an HTML error view with every composer skipped,
        // same crash class as #3903/#3806.
        $response = $this->post('/benchmark');

        // Assert
        $response->assertStatus(405);
        $response->assertHeader('Allow', 'GET, HEAD');
        $response->assertJson(['message' => 'Method Not Allowed']);
    }

    #[Test]
    public function render_givenJsonWrongMethodToExistingRoute_returns405JsonWithAllowHeader(): void
    {
        // Act - the home page is GET only, so a POST is a genuine method mismatch thrown by the router
        $response = $this->postJson(route('home'));

        // Assert
        $response->assertStatus(405);
        $response->assertHeader('Allow', 'GET, HEAD');
        $response->assertJson(['message' => 'Method Not Allowed']);
    }

    /**
     * Dispatches a GET request through the kernel with $path preserved verbatim, bypassing the
     * trailing-slash trim MakesHttpRequests::call() applies to every request built via
     * $this->get()/getJson()/etc.
     *
     * @return TestResponse<\Illuminate\Http\Response>
     */
    private function dispatchWithRawPath(string $path): TestResponse
    {
        $kernel = $this->app->make(HttpKernel::class);

        $response = $kernel->handle($request = Request::create($path, 'GET'));

        $kernel->terminate($request, $response);

        return TestResponse::fromBaseResponse($response);
    }
}
