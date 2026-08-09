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
        // Act - /ajax/profile/adfree/{user} is registered for POST and DELETE, but not GET. No view
        // composers ever run on /ajax/ (ViewService::shouldLoadViewVariables()), so this must never
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
        // model isn't found. Handler::isApiRequest() used to miss this exact shape - it compared
        // Request::decodedPath() (trailing slash trimmed to "api") against 'api/', which never
        // matches - so this rendered an HTML error view with every view composer skipped, since
        // ViewService::shouldLoadViewVariables() blacklists the same path by its raw, slash-
        // preserving form and does NOT run composers for it (#3903).
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
        // Act - "/api" (no trailing slash) is a different shape from "/api/" above: Laravel's own
        // Request::path()/getPathInfo() disagree on it, so ViewService::shouldLoadViewVariables()
        // (raw pathInfo "/api", not blacklisted) and isApiRequest() (delegates to the same check)
        // agree it's NOT blacklisted - view composers run normally here and it never crashed, so
        // it's the ordinary HTML 404 page rather than a forced-JSON response.
        $response = $this->get('/api');

        // Assert
        $response->assertStatus(404);
        $response->assertSee(__('view_errors.404.title'));
    }

    #[Test]
    public function fallback_givenPlainPostRequestToGetOnlyBenchmarkRoute_returns405Json(): void
    {
        // Act - '/benchmark' is the third VIEW_VARIABLES_URL_BLACKLIST entry (ViewService) besides
        // '/ajax/' and '/api/' - the old hand-rolled isApiRequest() check never covered it at all,
        // so any error there (this route is GET|HEAD only) rendered an HTML error view with every
        // composer skipped, same crash class as #3903/#3806.
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
