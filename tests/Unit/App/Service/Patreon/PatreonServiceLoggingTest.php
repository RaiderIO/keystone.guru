<?php

namespace Tests\Unit\App\Service\Patreon;

use App\Logging\StructuredLogging;
use App\Service\Patreon\Logging\PatreonServiceLogging;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\AbstractLogger;
use Stringable;
use Tests\TestCases\PublicTestCase;

#[Group('Patreon')]
#[Group('Logging')]
final class PatreonServiceLoggingTest extends PublicTestCase
{
    #[Test]
    public function linkToUserAccountTokens_givenATokenResponse_logsItWithoutTheTokenValues(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production']);
        StructuredLogging::flushConfigCache();

        $logger = new class extends AbstractLogger {
            /** @var array<int, array{message: string, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = ['message' => (string)$message, 'context' => $context];
            }
        };

        $log = new class($logger) extends PatreonServiceLogging {
            public function __construct(private readonly AbstractLogger $capturingLogger)
            {
                parent::__construct();
            }

            protected function getDefaultLoggers(): array
            {
                return [$this->capturingLogger];
            }
        };

        try {
            // Act
            $log->linkToUserAccountTokens([
                'access_token'  => 'access-token-value',
                'refresh_token' => 'refresh-token-value',
                'scope'         => 'identity identity.memberships',
                'expires_in'    => 2678400,
            ]);
        } finally {
            StructuredLogging::flushConfigCache();
        }

        // Assert
        self::assertCount(1, $logger->records);
        self::assertSame('PatreonServiceLogging::linkToUserAccountTokens', $logger->records[0]['message']);

        $serializedContext = json_encode($logger->records[0]['context']);
        self::assertStringNotContainsString('access-token-value', $serializedContext);
        self::assertStringNotContainsString('refresh-token-value', $serializedContext);
        self::assertSame(
            ['scope' => 'identity identity.memberships', 'expires_in' => 2678400],
            $logger->records[0]['context']['tokens'],
        );
    }
}
