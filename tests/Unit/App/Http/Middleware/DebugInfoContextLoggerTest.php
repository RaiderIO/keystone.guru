<?php

namespace Tests\Unit\App\Http\Middleware;

use App\Http\Middleware\DebugInfoContextLogger;
use App\Http\Middleware\Logging\DebugInfoContextLoggerLoggingInterface;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('DebugInfoContextLogger')]
final class DebugInfoContextLoggerTest extends PublicTestCase
{
    #[Test]
    public function buildLogContext_givenProductionUrlWithSensitiveQueryParameters_redactsThem(): void
    {
        // Arrange
        config(['app.type' => 'production']);
        $middleware = new DebugInfoContextLogger($this->createMock(DebugInfoContextLoggerLoggingInterface::class));
        $request    = Request::create('https://keystone.guru/patreon-link', 'GET', [
            'code'  => 'oauthcode123',
            'state' => 'oauthstate456',
            'page'  => '2',
        ]);
        $previewRequest = Request::create('https://keystone.guru/route/x/abc/x/preview/4', 'GET', [
            'secret' => 'c0ffee',
            'z'      => '1',
        ]);

        // Act
        $context        = $middleware->buildLogContext($request);
        $previewContext = $middleware->buildLogContext($previewRequest);

        // Assert
        self::assertSame('https://keystone.guru/patreon-link?code=[Filtered]&page=2&state=[Filtered]', $context['url']);
        self::assertSame('https://keystone.guru/route/x/abc/x/preview/4?secret=[Filtered]&z=1', $previewContext['url']);
    }

    #[Test]
    public function buildLogContext_givenLocalAppType_omitsTheUrl(): void
    {
        // Arrange
        config(['app.type' => 'local']);
        $middleware = new DebugInfoContextLogger($this->createMock(DebugInfoContextLoggerLoggingInterface::class));
        $request    = Request::create('https://keystone.guru/patreon-link', 'GET', ['code' => 'oauthcode123']);

        // Act
        $context = $middleware->buildLogContext($request);

        // Assert
        self::assertArrayHasKey('correlationId', $context);
        self::assertArrayNotHasKey('url', $context);
    }
}
