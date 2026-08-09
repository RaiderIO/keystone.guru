<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\View\ViewServiceInterface;
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
        // Act - "/api" (no trailing slash) is a different shape from "/api/" above: its raw
        // pathInfo is "/api", which ViewService::shouldLoadViewVariables() does NOT blacklist
        // (only the slash-suffixed '/api/' prefix is), so isApiRequest() - delegating to that same
        // check - agrees it's not an API request either. This never crashed even before #3903, so
        // it's the ordinary HTML 404 page rather than a forced-JSON response - not a regression to
        // fix, just the other half of the boundary the fix above sits on.
        $response = $this->get('/api');

        // Assert
        $response->assertStatus(404);
        $response->assertSee(__('view_errors.404.title'));
    }

    #[Test]
    public function shouldLoadViewVariables_givenBareApiPathVersusApiPathWithTrailingSlash_disagreesOnBlacklisting(): void
    {
        // This pins the exact boundary the two tests above rely on, independent of whether view
        // composers actually register - KeystoneGuruServiceProvider::boot() only skips
        // registering them outside of `app()->runningUnitTests()`, so PHPUnit can't observe a
        // composer-skip regression through an HTTP response either way.
        $viewService = app(ViewServiceInterface::class);

        $this->assertFalse($viewService->shouldLoadViewVariables('/api/'), "'/api/' must stay blacklisted");
        $this->assertTrue($viewService->shouldLoadViewVariables('/api'), "'/api' (no trailing slash) must stay outside the blacklist");
    }

    #[Test]
    public function fallback_givenPlainWrongMethodRequestToWhitelistedAjaxSearchRoute_returns405HtmlPage(): void
    {
        // Act - pins an intentional behavior change: '/ajax/search' is one of the handful of
        // /ajax/ routes VIEW_VARIABLES_URL_WHITELIST carves back out of the blacklist (it renders
        // an HTML fragment on success), so composers run there and isApiRequest() - now delegating
        // to the same whitelist-aware check - no longer force-JSONs its errors either. Before this
        // fix, isApiRequest()'s blanket '/ajax/' prefix match forced JSON here regardless of the
        // whitelist. This is safe (composers running means no #3806/#3903-style crash), but it is
        // an observable response-shape change for anything reading this route's error body as
        // JSON - contrast with the still-forced-JSON '/ajax/profile/adfree/1' case above, which
        // stays blacklisted because it isn't one of the three whitelisted paths.
        $response = $this->post('/ajax/search');

        // Assert - Allow also lists PATCH/DELETE: 'ajax/{dungeonRoute}' matches this exact URI too,
        // treating "search" as the dungeonRoute slug
        $response->assertStatus(405);
        $response->assertHeader('Allow', 'GET, HEAD, PATCH, DELETE');
        $response->assertSee(__('view_errors.405.title'));
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
