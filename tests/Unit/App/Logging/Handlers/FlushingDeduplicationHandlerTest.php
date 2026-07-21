<?php

namespace Tests\Unit\App\Logging\Handlers;

use App\Logging\Handlers\FlushingDeduplicationHandler;
use DateTimeImmutable;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
#[Group('FlushingDeduplicationHandler')]
class FlushingDeduplicationHandlerTest extends PublicTestCase
{
    #[Test]
    public function handle_GivenDuplicateErrorRecordsWithinWindow_ShouldForwardOnlyFirstRecord(): void
    {
        // Arrange
        $deduplicationStore = tempnam(sys_get_temp_dir(), 'dedup-test-');

        try {
            $testHandler = new TestHandler();
            $handler     = new FlushingDeduplicationHandler($testHandler, $deduplicationStore, Level::Error, 60);

            // Act
            $handler->handle($this->createLogRecord(Level::Error, 'Something broke'));
            $handler->handle($this->createLogRecord(Level::Error, 'Something broke'));

            // Assert
            self::assertCount(1, $testHandler->getRecords());
        } finally {
            @unlink($deduplicationStore);
        }
    }

    #[Test]
    public function handle_GivenDifferentErrorRecords_ShouldForwardAllRecords(): void
    {
        // Arrange
        $deduplicationStore = tempnam(sys_get_temp_dir(), 'dedup-test-');

        try {
            $testHandler = new TestHandler();
            $handler     = new FlushingDeduplicationHandler($testHandler, $deduplicationStore, Level::Error, 60);

            // Act
            $handler->handle($this->createLogRecord(Level::Error, 'Something broke'));
            $handler->handle($this->createLogRecord(Level::Error, 'Something else broke'));

            // Assert
            self::assertCount(2, $testHandler->getRecords());
        } finally {
            @unlink($deduplicationStore);
        }
    }

    #[Test]
    public function handle_GivenDuplicateRecordsBelowDeduplicationLevel_ShouldForwardAllRecords(): void
    {
        // Arrange
        $deduplicationStore = tempnam(sys_get_temp_dir(), 'dedup-test-');

        try {
            $testHandler = new TestHandler();
            $handler     = new FlushingDeduplicationHandler($testHandler, $deduplicationStore, Level::Error, 60);

            // Act
            $handler->handle($this->createLogRecord(Level::Warning, 'Something suspicious'));
            $handler->handle($this->createLogRecord(Level::Warning, 'Something suspicious'));

            // Assert
            self::assertCount(2, $testHandler->getRecords());
        } finally {
            @unlink($deduplicationStore);
        }
    }

    private function createLogRecord(Level $level, string $message): LogRecord
    {
        return new LogRecord(new DateTimeImmutable(), 'test', $level, $message);
    }
}
