<?php

namespace Tests\Unit\App\Service\CombatLog;

use App\Service\CombatLog\CombatLogServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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

//    #[Test]
//    #[Group('CombatLogService')]
//    #[DataProvider('parseCombatLogToEvents_GivenCombatLog_ShouldParseEventsWithoutErrors_DataProvider')]
//    public function parseCombatLogToEvents_GivenCombatLog_ShouldParseEventsWithoutErrors(string $combatLogPath): void
//    {
//        // Arrange
//        ini_set('memory_limit', '1G');
//        $log              = LoggingFixtures::createCombatLogServiceLogging($this);
//        $combatLogService = ServiceFixtures::getCombatLogServiceMock($this, $log);
//
//        // Act
//        $events = $combatLogService->parseCombatLogToEvents(
//            $combatLogPath
//        );
//
//        // Assert
//        Assert::assertNotCount(0, $events);
//
//        // Force garbage collection
//        unset($events);
//        gc_collect_cycles();
//    }
//
    /**
     * @return array<int, mixed>
     */
//    public static function parseCombatLogToEvents_GivenCombatLog_ShouldParseEventsWithoutErrors_DataProvider(): array
//    {
//        return [
//            [
//                __DIR__ . '/Fixtures/2_underrot/combat.log',
//            ],
//            [
//                __DIR__ . '/Fixtures/4_neltharus/combat.log',
//            ],
//            [
//                __DIR__ . '/Fixtures/5_freehold/combat.log',
//            ],
//            [
//                __DIR__ . '/Fixtures/18_neltharus/combat.log',
//            ],
//        ];
//    }
}
