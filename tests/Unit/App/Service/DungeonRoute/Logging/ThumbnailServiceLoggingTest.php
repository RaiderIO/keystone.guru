<?php

namespace Tests\Unit\App\Service\DungeonRoute\Logging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
#[Group('ThumbnailService')]
final class ThumbnailServiceLoggingTest extends PublicTestCase
{
    private const string SECRET = 'super-secret-preview-value';

    #[Test]
    public function doCreateThumbnailProcessStart_givenCommandLineWithSecret_logsCommandLineWithoutSecretParameter(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

        $logger      = LoggingFixtures::createLogManager($this);
        $log         = new TestableThumbnailServiceLogging($logger);
        $commandLine = sprintf(
            'node thumbnail.js "https://keystone.guru/preview?dungeonroute=abc&secret=%s&z=2" 1920 1080',
            self::SECRET,
        );

        $logger
            ->expects($this->once())
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []): void {
                self::assertArrayHasKey('commandLine', $context);
                self::assertStringNotContainsString('secret', $context['commandLine']);
                self::assertStringContainsString('?dungeonroute=abc&z=2"', $context['commandLine']);
            });

        // Act
        $log->doCreateThumbnailProcessStart($commandLine);

        // Assert
        // Already checked in the callback
    }

    #[Test]
    public function doCreateThumbnailError_givenPreviewUrlWithSecretAsLastParameter_logsPreviewUrlWithoutSecretParameter(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

        $logger     = LoggingFixtures::createLogManager($this);
        $log        = new TestableThumbnailServiceLogging($logger);
        $previewUrl = sprintf('https://keystone.guru/preview?dungeonroute=abc&secret=%s', self::SECRET);

        $logger
            ->expects($this->once())
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []): void {
                self::assertArrayHasKey('previewUrl', $context);
                self::assertSame('https://keystone.guru/preview?dungeonroute=abc', $context['previewUrl']);
            });

        // Act
        $log->doCreateThumbnailError('some error', $previewUrl, 'standard', 1234);

        // Assert
        // Already checked in the callback
    }

    #[Test]
    #[DataProvider('doCreateThumbnailError_givenErrorsWithSecret_logsErrorsWithoutSecretParameter_dataProvider')]
    public function doCreateThumbnailError_givenErrorsWithSecret_logsErrorsWithoutSecretParameter(
        string $errors,
        string $expectedErrors,
    ): void {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

        $logger = LoggingFixtures::createLogManager($this);
        $log    = new TestableThumbnailServiceLogging($logger);

        $logger
            ->expects($this->once())
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []) use ($expectedErrors): void {
                self::assertSame($expectedErrors, $context['errors']);
            });

        // Act
        $log->doCreateThumbnailError($errors, 'https://keystone.guru/preview', 'standard', 1234);

        // Assert
        // Already checked in the callback
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function doCreateThumbnailError_givenErrorsWithSecret_logsErrorsWithoutSecretParameter_dataProvider(): array
    {
        return [
            'secret as a middle parameter, on several lines' => [
                sprintf(
                    "Render failed after 10012ms for http://nginx/preview/4?dungeonroute=abc&secret=%s&z=1\nRESPONSE 500 http://nginx/preview/4?secret=%s&z=1",
                    self::SECRET,
                    self::SECRET,
                ),
                "Render failed after 10012ms for http://nginx/preview/4?dungeonroute=abc&z=1\nRESPONSE 500 http://nginx/preview/4?z=1",
            ],
            'secret as the only parameter' => [
                sprintf('Render failed after 10012ms for http://nginx/preview/4?secret=%s', self::SECRET),
                'Render failed after 10012ms for http://nginx/preview/4',
            ],
            'no secret' => [
                'TimeoutError: Waiting for selector `#finished_loading` failed',
                'TimeoutError: Waiting for selector `#finished_loading` failed',
            ],
        ];
    }

    #[Test]
    #[DataProvider('doCreateThumbnailRetryableOutcome_givenErrorsWithSecret_logsWarningWithoutSecret_dataProvider')]
    public function doCreateThumbnailRetryableOutcome_givenErrorsWithSecret_logsWarningWithoutSecret(string $logMethod): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

        $logger     = LoggingFixtures::createLogManager($this);
        $log        = new TestableThumbnailServiceLogging($logger);
        $previewUrl = sprintf('http://nginx/preview/4?secret=%s&z=1', self::SECRET);
        $errors     = sprintf('Page load 1 of 3 failed after 2034ms for %s: Waiting failed', $previewUrl);

        $logger
            ->expects($this->once())
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []): void {
                self::assertSame('WARNING', $level);
                self::assertSame('Page load 1 of 3 failed after 2034ms for http://nginx/preview/4?z=1: Waiting failed', $context['errors']);
                self::assertSame('http://nginx/preview/4?z=1', $context['previewUrl']);
            });

        // Act
        $log->{$logMethod}($errors, $previewUrl, 'standard', 1234);

        // Assert
        // Already checked in the callback
    }

    /**
     * @return array<string, array{string}>
     */
    public static function doCreateThumbnailRetryableOutcome_givenErrorsWithSecret_logsWarningWithoutSecret_dataProvider(): array
    {
        return [
            'failed with attempts remaining' => ['doCreateThumbnailErrorWillRetry'],
            'recovered after a reload'       => ['doCreateThumbnailRecoveredAfterReload'],
        ];
    }

    #[Test]
    public function doCreateThumbnailBlankImageRejected_givenPreviewUrlWithoutSecret_logsPreviewUrlUnchanged(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

        $logger     = LoggingFixtures::createLogManager($this);
        $log        = new TestableThumbnailServiceLogging($logger);
        $previewUrl = 'https://keystone.guru/preview?dungeonroute=abc&z=2';

        $logger
            ->expects($this->once())
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []) use ($previewUrl): void {
                self::assertSame($previewUrl, $context['previewUrl']);
            });

        // Act
        $log->doCreateThumbnailBlankImageRejected('/tmp/file.png', $previewUrl, 'standard');

        // Assert
        // Already checked in the callback
    }
}
