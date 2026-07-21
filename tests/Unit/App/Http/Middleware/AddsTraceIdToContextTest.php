<?php

namespace Tests\Unit\App\Http\Middleware;

use App\Http\Middleware\AddsTraceIdToContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('AddsTraceIdToContext')]
class AddsTraceIdToContextTest extends PublicTestCase
{
    #[Test]
    public function handle_GivenRequest_ShouldAddTraceIdToContext(): void
    {
        // Arrange
        $middleware = new AddsTraceIdToContext();
        $request    = Request::create('/');

        // Act
        $response = $middleware->handle($request, static fn() => new Response());

        // Assert
        self::assertInstanceOf(Response::class, $response);
        self::assertTrue(Context::has('trace_id'));
        self::assertTrue(Str::isUuid(Context::get('trace_id')));
    }
}
