<?php

namespace Tests\Unit\App\Service\Reverb;

use App\Service\Reverb\ReverbHttpApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Reverb')]
#[Group('ReverbHttpApiService')]
final class ReverbHttpApiServiceTest extends PublicTestCase
{
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
    }

    #[Test]
    public function getChannelUsers_givenNonNotFoundClientError_throws(): void
    {
        // Arrange
        $service = $this->makeService(new Response(StatusCode::INTERNAL_SERVER_ERROR, [], 'server error'));

        // Assert
        $this->expectException(\Throwable::class);

        // Act
        $service->getChannelUsers('presence-app-route-edit.somekey');
    }

    private function makeService(Response $response): ReverbHttpApiService
    {
        $service = new ReverbHttpApiService();

        $mock         = new MockHandler([$response]);
        $handlerStack = HandlerStack::create($mock);
        $client       = new Client(['handler' => $handlerStack]);

        $reflection = new ReflectionClass($service);
        $property   = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        return $service;
    }
}
