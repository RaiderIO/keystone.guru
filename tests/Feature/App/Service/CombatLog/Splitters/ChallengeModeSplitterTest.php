<?php

namespace Tests\Feature\App\Service\CombatLog\Splitters;

use App\Service\CombatLog\Exceptions\CombatLogParseException;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Splitters\ChallengeModeSplitter;
use ArrayObject;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesParseCountingCombatLogService;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('ChallengeModeSplitter')]
final class ChallengeModeSplitterTest extends PublicTestCase
{
    use CreatesParseCountingCombatLogService;

    private const array VORTEX_PINNACLE_RUN = [
        '5/13 16:22:53.381  COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,10.1.0,PROJECT_ID,1',
        '5/13 16:22:53.381  ZONE_CHANGE,657,"The Vortex Pinnacle",23',
        '5/13 16:22:53.381  MAP_CHANGE,325,"The Vortex Pinnacle",-111.332031,-1457.150024,1107.791016,-910.934998',
        '5/13 16:24:21.028  CHALLENGE_MODE_START,"The Vortex Pinnacle",657,438,10,[9,124]',
        '5/13 16:40:02.910  CHALLENGE_MODE_END,657,1,10,938397,95.000000,1070.282471',
    ];

    #[Test]
    public function splitCombatLog_givenOneChallengeMode_parsesTheCombatLogOnce(): void
    {
        // Arrange
        $directory = $this->createCombatLogDirectory();

        try {
            $combatLogPath   = $this->writeCombatLog($directory, self::VORTEX_PINNACLE_RUN);
            $parsedFilePaths = new ArrayObject();
            $splitter        = new ChallengeModeSplitter($this->createParseCountingCombatLogService($parsedFilePaths));

            // Act
            $result = $splitter->splitCombatLog($combatLogPath);

            // Assert
            $this->assertCount(1, $result);
            $this->assertFileExists($result->first());
            $this->assertSame([$combatLogPath], $parsedFilePaths->getArrayCopy());
        } finally {
            File::deleteDirectory($directory);
        }
    }

    #[Test]
    public function splitCombatLog_givenUnsupportedChallengeModeAfterACompletedRun_throwsDungeonNotSupportedAndLeavesNoSplitFiles(): void
    {
        // Arrange
        $directory = $this->createCombatLogDirectory();

        try {
            $combatLogPath = $this->writeCombatLog($directory, [
                ...self::VORTEX_PINNACLE_RUN,
                '5/13 16:50:21.028  CHALLENGE_MODE_START,"Nowhere",657,999999,10,[9,124]',
            ]);
            $splitter = new ChallengeModeSplitter($this->createParseCountingCombatLogService(new ArrayObject()));

            $thrownException = null;

            // Act
            try {
                $splitter->splitCombatLog($combatLogPath);
            } catch (CombatLogParseException $exception) {
                $thrownException = $exception;
            }

            // Assert
            $this->assertInstanceOf(CombatLogParseException::class, $thrownException);
            $this->assertInstanceOf(DungeonNotSupportedException::class, $thrownException->getPrevious());
            $this->assertSame([basename($combatLogPath)], array_values(array_diff(scandir($directory), ['.', '..'])));
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function createCombatLogDirectory(): string
    {
        $directory = sprintf('%s/challenge_mode_splitter_test_%d', sys_get_temp_dir(), random_int(1, PHP_INT_MAX));
        File::makeDirectory($directory);

        return $directory;
    }

    /**
     * @param array<int, string> $lines
     */
    private function writeCombatLog(string $directory, array $lines): string
    {
        $combatLogPath = sprintf('%s/WoWCombatLog-051323_095734.txt', $directory);
        file_put_contents($combatLogPath, implode(PHP_EOL, $lines) . PHP_EOL);

        return $combatLogPath;
    }
}
