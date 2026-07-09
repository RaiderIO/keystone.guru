<?php

namespace Tests\Feature\Controller;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('ErrorResponse')]
final class ErrorResponseTest extends PublicTestCase
{
    #[Test]
    public function fallback_givenGetRequestToDeleteOnlyRoute_returns405HtmlPage(): void
    {
        // Act - /ajax/profile/adfree/{user} is registered for POST and DELETE, but not GET
        $response = $this->get('/ajax/profile/adfree/1');

        // Assert
        $response->assertStatus(405);
        $response->assertHeader('Allow', 'POST, DELETE');
        $response->assertSee(__('view_errors.405.title'));
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
    public function render_givenJsonWrongMethodToExistingRoute_returns405JsonWithAllowHeader(): void
    {
        // Act - the home page is GET only, so a POST is a genuine method mismatch thrown by the router
        $response = $this->postJson(route('home'));

        // Assert
        $response->assertStatus(405);
        $response->assertHeader('Allow', 'GET, HEAD');
        $response->assertJson(['message' => 'Method Not Allowed']);
    }
}
