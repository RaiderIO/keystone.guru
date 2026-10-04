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

    #[Test]
    public function handle_GivenRequest_ShouldAddTraceIdBeforeTheRequestIsHandled(): void
    {
        // Arrange
        $middleware       = new AddsTraceIdToContext();
        $traceIdWhileNext = null;

        // Act
        $middleware->handle(Request::create('/'), static function () use (&$traceIdWhileNext): Response {
            $traceIdWhileNext = Context::get('trace_id');

            return new Response();
        });

        // Assert
        self::assertNotNull($traceIdWhileNext, 'Log lines written while handling the request must carry the trace_id');
        self::assertSame($traceIdWhileNext, Context::get('trace_id'));
    }

    #[Test]
    public function handle_GivenTwoRequests_ShouldGiveEachItsOwnTraceId(): void
    {
        // Arrange
        $middleware = new AddsTraceIdToContext();

        // Act
        $middleware->handle(Request::create('/'), static fn() => new Response());
        $firstTraceId = Context::get('trace_id');
        $middleware->handle(Request::create('/'), static fn() => new Response());
        $secondTraceId = Context::get('trace_id');

        // Assert
        self::assertTrue(Str::isUuid($firstTraceId));
        self::assertTrue(Str::isUuid($secondTraceId));
        self::assertNotSame($firstTraceId, $secondTraceId);
    }
}
