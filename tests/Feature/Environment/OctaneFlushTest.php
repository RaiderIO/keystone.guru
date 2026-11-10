<?php

namespace Tests\Feature\Environment;

use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Html\Html;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Spatie's Html builder is a container singleton that keeps the Request it was constructed with, so Octane has to
 * forget it after every request for the next form to read its own old input and CSRF token.
 */
#[Group('Environment')]
#[Group('Octane')]
final class OctaneFlushTest extends TestCase
{
    #[Test]
    public function octaneFlush_givenDefaultConfig_containsHtmlBuilder(): void
    {
        // Arrange
        $flushedBindings = config('octane.flush');

        // Act
        $containsHtmlBuilder = in_array(Html::class, $flushedBindings, true);

        // Assert
        $this->assertTrue($containsHtmlBuilder, 'octane.flush must list Spatie\Html\Html');
    }

    #[Test]
    public function flushTemporaryContainerInstances_givenSecondRequest_returnsHtmlBuilderReadingSecondRequestState(): void
    {
        // Arrange
        $this->app->forgetInstance(Html::class);
        $firstRequest  = $this->createRequestWithSession('first-request-token', 'first-request-input');
        $secondRequest = $this->createRequestWithSession('second-request-token', 'second-request-input');

        $this->app->instance('request', $firstRequest);
        $firstInput = html()->text('name')->toHtml();
        $firstToken = html()->token()->toHtml();

        // Act
        $this->app->make(FlushTemporaryContainerInstances::class)->handle(
            new RequestTerminated($this->app, $this->app, $firstRequest, new Response()),
        );
        $this->app->instance('request', $secondRequest);
        $secondInput = html()->text('name')->toHtml();
        $secondToken = html()->token()->toHtml();

        // Assert
        $this->assertStringContainsString('first-request-input', $firstInput);
        $this->assertStringContainsString('first-request-token', $firstToken);
        $this->assertStringContainsString('second-request-input', $secondInput);
        $this->assertStringContainsString('second-request-token', $secondToken);
    }

    private function createRequestWithSession(string $token, string $oldInput): Request
    {
        $session = new Store('octane-flush-test', new ArraySessionHandler(10));
        $session->put('_token', $token);
        $session->flashInput(['name' => $oldInput]);

        $request = Request::create('/');
        $request->setLaravelSession($session);

        return $request;
    }
}
