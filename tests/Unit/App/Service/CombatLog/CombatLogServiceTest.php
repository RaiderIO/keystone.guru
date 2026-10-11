<?php

namespace Tests\Unit\App\Service\CombatLog;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Service\CombatLog\CombatLogService;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Exceptions\CombatLogParseException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RedisException;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;
use ZipArchive;

#[Group('CombatLogService')]
final class CombatLogServiceTest extends PublicTestCase
{
    #[Test]
    public function extractCombatLog_givenZipWithDifferingInnerName_returnsPathToActualEntry(): void
    {
        // Arrange — the inner entry name does not match the outer (temp) file name, as happens when a
        // segment download is saved under a generated name such as run_0_segment_1.zip.
        $contents     = "COMBAT_LOG_VERSION,21\nZONE_CHANGE,1234\n";
        $innerEntry   = sprintf('WoWCombatLog-%d.txt', random_int(1, PHP_INT_MAX));
        $zipFilePath  = sprintf('%s/run_0_segment_%d.zip', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        $expectedPath = sprintf('/tmp/%s', $innerEntry);

        $zip = new ZipArchive();
        $zip->open($zipFilePath, ZipArchive::CREATE);
        $zip->addFromString($innerEntry, $contents);
        $zip->close();

        $extractedFilePath = null;

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app(CombatLogServiceInterface::class);

            // Act
            $extractedFilePath = $combatLogService->extractCombatLog($zipFilePath);

            // Assert
            $this->assertSame($expectedPath, $extractedFilePath);
            $this->assertFileExists($extractedFilePath);
            $this->assertSame($contents, file_get_contents($extractedFilePath));
        } finally {
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
            if ($extractedFilePath !== null && file_exists($extractedFilePath)) {
                unlink($extractedFilePath);
            }
        }
    }

    #[Test]
    public function extractCombatLog_givenNonZipFile_returnsNull(): void
    {
        // Arrange — plain text files (such as the already-decompressed Raider.IO segments) are parsed as-is.
        $txtFilePath = sprintf('%s/keystone_test_combatlog_%d.txt', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        file_put_contents($txtFilePath, "COMBAT_LOG_VERSION,21\nZONE_CHANGE,1234\n");

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app(CombatLogServiceInterface::class);

            // Act
            $extractedFilePath = $combatLogService->extractCombatLog($txtFilePath);

            // Assert
            $this->assertNull($extractedFilePath);
        } finally {
            if (file_exists($txtFilePath)) {
                unlink($txtFilePath);
            }
        }
    }

    #[Test]
    public function parseCombatLog_givenCallbackThrowsRedisException_rethrowsUnwrapped(): void
    {
        // Arrange — a dropped Redis/Valkey connection mid-parse (#3791) is a transient infra failure, not an
        // unparsable line. It must propagate as-is (not wrapped in CombatLogParseException) so the caller's
        // retry logic applies instead of the blip being recorded as a permanent parse failure.
        $filePath = sprintf('%s/keystone_test_combatlog_%d.txt', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        file_put_contents($filePath, "COMBAT_LOG_VERSION,21\nZONE_CHANGE,1234\n");

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app(CombatLogServiceInterface::class);

            // Assert
            $this->expectException(RedisException::class);
            $this->expectExceptionMessage('read error on connection to tcp://ksg-valkey:6379');

            // Act
            $combatLogService->parseCombatLog($filePath, function (): void {
                throw new RedisException('read error on connection to tcp://ksg-valkey:6379');
            });
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    #[Test]
    public function parseCombatLog_givenCallbackThrowsOtherException_wrapsInCombatLogParseException(): void
    {
        // Arrange — genuine parse errors are still wrapped so the failing line/number can be recorded.
        $filePath = sprintf('%s/keystone_test_combatlog_%d.txt', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        file_put_contents($filePath, "COMBAT_LOG_VERSION,21\nZONE_CHANGE,1234\n");

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app(CombatLogServiceInterface::class);

            // Assert
            $this->expectException(CombatLogParseException::class);

            // Act
            $combatLogService->parseCombatLog($filePath, function (): void {
                throw new InvalidArgumentException('Unbalanced quotes');
            });
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    #[Test]
    public function parseCombatLog_givenCallbackThrowsOnALine_reportsThatLineAndItsNumber(): void
    {
        // Arrange
        $filePath = sprintf('%s/keystone_test_combatlog_%d.txt', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        file_put_contents($filePath, "COMBAT_LOG_VERSION,21\nZONE_CHANGE,1234\n");

        $parseException = null;

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app(CombatLogServiceInterface::class);

            // Act
            try {
                $combatLogService->parseCombatLog($filePath, function (int $combatLogVersion, bool $advancedLoggingEnabled, string $rawEvent): void {
                    if (str_starts_with($rawEvent, 'ZONE_CHANGE')) {
                        throw new InvalidArgumentException('Unbalanced quotes');
                    }
                });
            } catch (CombatLogParseException $exception) {
                $parseException = $exception;
            }

            // Assert
            $this->assertNotNull($parseException);
            $this->assertSame(2, $parseException->lineNumber);
            $this->assertSame('ZONE_CHANGE,1234', $parseException->rawLine);
            $this->assertSame('Unbalanced quotes', $parseException->getMessage());
            $this->assertSame(InvalidArgumentException::class, $parseException->getOriginalExceptionClass());
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    #[Test]
    public function parseCombatLog_givenZip_removesTheExtractedFileAfterwards(): void
    {
        // Arrange
        $innerEntry    = sprintf('WoWCombatLog-%d.txt', random_int(1, PHP_INT_MAX));
        $zipFilePath   = sprintf('%s/run_0_segment_%d.zip', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        $extractedPath = sprintf('/tmp/%s', $innerEntry);

        $zip = new ZipArchive();
        $zip->open($zipFilePath, ZipArchive::CREATE);
        $zip->addFromString($innerEntry, "COMBAT_LOG_VERSION,21\nZONE_CHANGE,1234\n");
        $zip->close();

        $parsedLines = [];

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app(CombatLogServiceInterface::class);

            // Act
            $combatLogService->parseCombatLog($zipFilePath, function (int $combatLogVersion, bool $advancedLoggingEnabled, string $rawEvent) use (&$parsedLines): void {
                $parsedLines[] = trim($rawEvent);
            });

            // Assert - the archive's contents were read, and the copy unpacked for that is gone again
            $this->assertSame(['COMBAT_LOG_VERSION,21', 'ZONE_CHANGE,1234'], $parsedLines);
            $this->assertFileDoesNotExist($extractedPath);
        } finally {
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
            if (file_exists($extractedPath)) {
                unlink($extractedPath);
            }
        }
    }

    #[Test]
    public function parseCombatLog_givenALineBeforeAnyVersionHeader_passesTheLatestRetailVersion(): void
    {
        // Arrange
        $filePath = sprintf('%s/keystone_test_combatlog_%d.txt', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        file_put_contents($filePath, "ZONE_CHANGE,1234\n");

        $passedCombatLogVersions = [];

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app(CombatLogServiceInterface::class);

            // Act
            $combatLogService->parseCombatLog($filePath, function (int $combatLogVersion) use (&$passedCombatLogVersions): void {
                $passedCombatLogVersions[] = $combatLogVersion;
            });

            // Assert
            $this->assertSame([CombatLogVersion::getLatestRetail()], $passedCombatLogVersions);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    #[Test]
    public function parseCombatLog_givenTwoLogsOfTheSameUnregisteredBuild_reportsTheBuildOnce(): void
    {
        // Arrange
        $unregisteredCombatLogVersion = 22_012_009_009;
        $filePath                     = sprintf('%s/keystone_test_combatlog_%d.txt', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        file_put_contents($filePath, "10/11/2026 21:00:00.0000  COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.9.9,PROJECT_ID,1\n");

        $log = LoggingFixtures::createCombatLogServiceLogging($this);
        $log->expects($this->once())
            ->method('parseCombatLogUnregisteredCombatLogVersion')
            ->with($unregisteredCombatLogVersion);

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app()->make(CombatLogService::class, ['log' => $log]);

            // Act
            foreach ([1, 2] as $parse) {
                $combatLogService->parseCombatLog(
                    $filePath,
                    static fn(int $combatLogVersion, bool $advancedLoggingEnabled, string $rawEvent) => new CombatLogEntry($rawEvent)->parseEvent([], $combatLogVersion),
                );
            }
        } finally {
            Cache::forget(sprintf('combatlog:unregistered_combat_log_version:%d', $unregisteredCombatLogVersion));
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    #[Test]
    public function parseCombatLog_givenARegisteredBuild_doesNotReportIt(): void
    {
        // Arrange
        $filePath = sprintf('%s/keystone_test_combatlog_%d.txt', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        file_put_contents($filePath, "10/11/2026 21:00:00.0000  COMBAT_LOG_VERSION,22,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,12.1.0,PROJECT_ID,1\n");

        $log = LoggingFixtures::createCombatLogServiceLogging($this);
        $log->expects($this->once())
            ->method('parseCombatLogParseEventsChangedCombatLogVersion')
            ->with(CombatLogVersion::RETAIL_12_1_0, true);
        $log->expects($this->never())
            ->method('parseCombatLogUnregisteredCombatLogVersion');

        try {
            /** @var CombatLogServiceInterface $combatLogService */
            $combatLogService = app()->make(CombatLogService::class, ['log' => $log]);

            // Act
            $combatLogService->parseCombatLog(
                $filePath,
                static fn(int $combatLogVersion, bool $advancedLoggingEnabled, string $rawEvent) => new CombatLogEntry($rawEvent)->parseEvent([], $combatLogVersion),
            );
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
