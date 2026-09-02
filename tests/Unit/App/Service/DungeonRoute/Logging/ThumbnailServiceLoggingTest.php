<?php

namespace Tests\Unit\App\Service\DungeonRoute\Logging;

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
    public function doCreateThumbnailProcessStart_givenCommandLineWithSecret_logsRedactedCommandLine(): void
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
                self::assertStringNotContainsString(self::SECRET, $context['commandLine']);
                self::assertStringContainsString('secret=[redacted]&z=2', $context['commandLine']);
            });

        // Act
        $log->doCreateThumbnailProcessStart($commandLine);

        // Assert
        // Already checked in the callback
    }

    #[Test]
    public function doCreateThumbnailError_givenPreviewUrlWithSecretAsLastParameter_logsRedactedPreviewUrl(): void
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
                self::assertStringNotContainsString(self::SECRET, $context['previewUrl']);
                self::assertSame('https://keystone.guru/preview?dungeonroute=abc&secret=[redacted]', $context['previewUrl']);
            });

        // Act
        $log->doCreateThumbnailError('some error', $previewUrl, 'standard', 1234);

        // Assert
        // Already checked in the callback
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
