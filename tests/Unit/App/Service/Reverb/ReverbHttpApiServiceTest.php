<?php

namespace Tests\Unit\App\Service\Reverb;

use App\Service\Reverb\ReverbHttpApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Laravel\Reverb\Application;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\Controller;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Reverb')]
#[Group('ReverbHttpApiService')]
final class ReverbHttpApiServiceTest extends PublicTestCase
{
    private MockHandler $mockHandler;

    /**
     * Guards #4152: Reverb 404s the "channel users" endpoint when the presence channel has no
     * active members yet (e.g. nobody joined it, or the last member left) - that's a routine
     * "no users" state, not evidence Reverb is down, so it must not bubble as an exception.
     */
    #[Test]
    public function getChannelUsers_givenChannelNotFound_returnsEmptyArray(): void
    {
        // Arrange
        $service = $this->makeService(new Response(StatusCode::NOT_FOUND, [], json_encode([
            'error' => 'Channel not found',
        ])));

        // Act
        $result = $service->getChannelUsers('presence-app-route-edit.somekey');

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function getChannelUsers_givenUsersPresent_returnsUsers(): void
    {
        // Arrange
        $service = $this->makeService(new Response(StatusCode::OK, [], json_encode([
            'users' => [['id' => '1'], ['id' => '2']],
        ])));

        // Act
        $result = $service->getChannelUsers('presence-app-route-edit.somekey');

        // Assert
        $this->assertSame([['id' => '1'], ['id' => '2']], $result);
        $this->assertSame(
            '/apps/123456/channels/presence-app-route-edit.somekey/users',
            $this->mockHandler->getLastRequest()?->getUri()->getPath(),
        );
    }

    /**
     * Reverb rejects an HTTP API request whose auth_signature is not the HMAC of method, path and query under the
     * app secret.
     */
    #[Test]
    public function getChannelUsers_givenARequest_signsItWithTheAppSecret(): void
    {
        // Arrange
        $this->freezeTime();
        $service = $this->makeService(new Response(StatusCode::OK, [], json_encode(['users' => []])));
        $path    = '/apps/123456/channels/presence-app-route-edit.somekey/users';

        // Act
        $service->getChannelUsers('presence-app-route-edit.somekey');

        // Assert
        parse_str($this->mockHandler->getLastRequest()?->getUri()->getQuery() ?? '', $query);
        $stringToSign = sprintf(
            "GET\n%s\nauth_key=test-key&auth_timestamp=%d&auth_version=1.0",
            $path,
            now()->getTimestamp(),
        );
        $this->assertSame(hash_hmac('sha256', $stringToSign, 'test-secret'), $query['auth_signature'] ?? null);
    }

    /**
     * Reverb 1.12+ rejects an HTTP API request without a recent auth_timestamp, with the same 401 a bad secret gives.
     */
    #[Test]
    public function getChannelUsers_givenARequest_sendsAuthKeyTimestampAndVersion(): void
    {
        // Arrange
        $this->freezeTime();
        $service = $this->makeService(new Response(StatusCode::OK, [], json_encode(['users' => []])));

        // Act
        $service->getChannelUsers('presence-app-route-edit.somekey');

        // Assert
        parse_str($this->mockHandler->getLastRequest()?->getUri()->getQuery() ?? '', $query);
        $this->assertSame('test-key', $query['auth_key'] ?? null);
        $this->assertSame((string)now()->getTimestamp(), $query['auth_timestamp'] ?? null);
        $this->assertSame('1.0', $query['auth_version'] ?? null);
    }

    #[Test]
    public function getChannelUsers_givenARequest_passesReverbsOwnSignatureVerification(): void
    {
        // Arrange
        $service = $this->makeService(new Response(StatusCode::OK, [], json_encode(['users' => []])));
        $service->getChannelUsers('presence-app-route-edit.somekey');
        $request = $this->mockHandler->getLastRequest();
        $this->assertNotNull($request);

        // Act
        $exception = $this->verifyWithReverb($request, 'test-secret');

        // Assert
        $this->assertNull($exception, $exception?->getMessage() ?? '');
    }

    /**
     * Positive control for the test above: Reverb's verifier does reject a request signed with another secret.
     */
    #[Test]
    public function getChannelUsers_givenAnotherServerSecret_failsReverbsOwnSignatureVerification(): void
    {
        // Arrange
        $service = $this->makeService(new Response(StatusCode::OK, [], json_encode(['users' => []])));
        $service->getChannelUsers('presence-app-route-edit.somekey');
        $request = $this->mockHandler->getLastRequest();
        $this->assertNotNull($request);

        // Act
        $exception = $this->verifyWithReverb($request, 'another-secret');

        // Assert
        $this->assertInstanceOf(HttpException::class, $exception);
        $this->assertSame(StatusCode::UNAUTHORIZED, $exception->getStatusCode());
    }

    /**
     * Reverb returns 400 (not 404) when the channel exists but isn't a presence channel - a
     * genuinely different failure that must still surface as an exception.
     */
    #[Test]
    public function getChannelUsers_givenNonNotFoundClientError_throws(): void
    {
        // Arrange
        $service = $this->makeService(new Response(StatusCode::BAD_REQUEST, [], 'not a presence channel'));

        // Assert
        $this->expectException(ClientException::class);

        // Act
        $service->getChannelUsers('presence-app-route-edit.somekey');
    }

    private function makeService(Response $response): ReverbHttpApiService
    {
        config([
            'reverb.apps.apps.0.app_id' => '123456',
            'reverb.apps.apps.0.key'    => 'test-key',
            'reverb.apps.apps.0.secret' => 'test-secret',
        ]);

        $service = new ReverbHttpApiService();

        $this->mockHandler = new MockHandler([$response]);
        $handlerStack      = HandlerStack::create($this->mockHandler);
        $client            = new Client(['handler' => $handlerStack]);

        $reflection = new ReflectionClass($service);
        $property   = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        return $service;
    }

    /**
     * Runs the request through Reverb's own Pusher HTTP API signature check, as the Reverb server would.
     */
    private function verifyWithReverb(RequestInterface $request, string $serverSecret): ?HttpException
    {
        config(['reverb.servers.reverb.path' => '']);

        $verifier = new class extends Controller {
            public function verifyRequest(RequestInterface $request, Application $application): void
            {
                parse_str($request->getUri()->getQuery(), $query);

                $this->query       = $query;
                $this->body        = (string)$request->getBody();
                $this->application = $application;

                $this->verifySignature($request);
            }
        };

        try {
            $verifier->verifyRequest($request, new Application('123456', 'test-key', $serverSecret, 60, 30, ['*'], 10_000));
        } catch (HttpException $httpException) {
            return $httpException;
        }

        return null;
    }
}
