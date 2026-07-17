<?php

namespace Tests\Feature\Controller\Webhook;

use App\Service\Discord\DiscordApiServiceInterface;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

#[Group('Controller')]
#[Group('Webhook')]
final class GithubWebhookControllerTest extends TestCase
{
    private const string SECRET = 'test-webhook-secret';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'keystoneguru.webhook.github.secret' => self::SECRET,
            'keystoneguru.webhook.github.url'    => 'https://discord.example/webhook',
        ]);
    }

    #[Test]
    public function github_givenDistinctCommitOnNormalBranch_sendsDiscordEmbeds(): void
    {
        // Arrange
        $this->expectDiscordEmbeds(1);

        // Act
        $response = $this->postWebhook('refs/heads/master', [$this->distinctCommit()]);

        // Assert
        $response->assertNoContent();
    }

    #[Test]
    public function github_givenMappingBranch_doesNotSendDiscordEmbeds(): void
    {
        // Arrange
        $this->expectDiscordEmbeds(0);

        // Act
        $response = $this->postWebhook('refs/heads/mapping', [$this->distinctCommit()]);

        // Assert
        $response->assertNoContent();
    }

    #[Test]
    public function github_givenVerificationScreenshotsBranch_doesNotSendDiscordEmbeds(): void
    {
        // Arrange
        $this->expectDiscordEmbeds(0);

        // Act
        $response = $this->postWebhook('refs/heads/verification-screenshots', [$this->distinctCommit()]);

        // Assert
        $response->assertNoContent();
    }

    #[Test]
    public function github_givenInvalidSignature_doesNotSendDiscordEmbeds(): void
    {
        // Arrange
        $this->expectDiscordEmbeds(0);
        $payload = json_encode(['ref' => 'refs/heads/master', 'commits' => [$this->distinctCommit()]], JSON_THROW_ON_ERROR);

        // Act
        $response = $this->call(
            'POST',
            route('webhook.github'),
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'X-Hub-Signature' => 'sha1=deadbeef',
                'Content-Type'    => 'application/json',
            ]),
            $payload,
        );

        // Assert
        $this->assertNotSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /**
     * Bind a mocked Discord service that expects `sendEmbeds` to be called exactly $times (0 = never).
     */
    private function expectDiscordEmbeds(int $times): void
    {
        $mock = Mockery::mock(DiscordApiServiceInterface::class);
        $this->instance(DiscordApiServiceInterface::class, $mock);

        /** @var \Mockery\Expectation $expectation */
        $expectation = $mock->shouldReceive('sendEmbeds');
        $expectation->times($times)->andReturnTrue();
    }

    /**
     * POST a GitHub push webhook with a valid HMAC signature computed over the raw JSON body, mirroring
     * how GitHub signs the request (content_type: json).
     *
     * @param array<int, array<string, mixed>> $commits
     *
     * @return TestResponse<Response>
     */
    private function postWebhook(string $ref, array $commits): TestResponse
    {
        $payload   = json_encode(['ref' => $ref, 'commits' => $commits], JSON_THROW_ON_ERROR);
        $signature = 'sha1=' . hash_hmac('sha1', $payload, self::SECRET);

        return $this->call(
            'POST',
            route('webhook.github'),
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'X-Hub-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ]),
            $payload,
        );
    }

    /**
     * A commit that passes every skip filter in the controller (distinct, not a system/merge commit), so
     * it would normally produce a Discord embed.
     *
     * @return array<string, mixed>
     */
    private function distinctCommit(): array
    {
        return [
            'message'   => 'Add a thing',
            'distinct'  => true,
            'url'       => 'https://github.com/RaiderIO/keystone.guru/commit/deadbeef',
            'timestamp' => '2026-07-17T12:00:00+00:00',
            'committer' => ['name' => 'Wouter', 'email' => 'wouter@example.com'],
            'added'     => [],
            'modified'  => [],
            'removed'   => [],
        ];
    }
}
