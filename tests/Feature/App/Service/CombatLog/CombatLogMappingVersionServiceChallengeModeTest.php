<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogMappingVersionService;
use ArrayObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesParseCountingCombatLogService;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('CombatLog')]
final class CombatLogMappingVersionServiceChallengeModeTest extends PublicTestCase
{
    use CreatesParseCountingCombatLogService;

    private const string ARCWAY_CHALLENGE_MODE_START = '5/15 21:20:10.941  CHALLENGE_MODE_START,"The Arcway",1841,209,2,[9]';

    #[Test]
    public function createMappingVersionFromChallengeMode_givenOneChallengeMode_parsesTheCombatLogOnce(): void
    {
        // Arrange
        $combatLogPath   = $this->writeCombatLog([self::ARCWAY_CHALLENGE_MODE_START]);
        $parsedFilePaths = new ArrayObject();
        $service         = $this->createService($parsedFilePaths);

        $createdMappingVersion = null;

        try {
            // Act
            $createdMappingVersion = $service->createMappingVersionFromChallengeMode($combatLogPath, $this->getRetailGameVersion());

            // Assert
            $this->assertNotNull($createdMappingVersion);
            $this->assertSame(Dungeon::where('challenge_mode_id', 209)->firstOrFail()->id, $createdMappingVersion->dungeon_id);
            $this->assertSame([$combatLogPath], $parsedFilePaths->getArrayCopy());
        } finally {
            unlink($combatLogPath);
            $createdMappingVersion?->delete();
        }
    }

    /**
     * @param array<int, string> $lines
     */
    #[Test]
    #[DataProvider('createMappingVersionFromChallengeMode_givenNotExactlyOneChallengeMode_DataProvider')]
    public function createMappingVersionFromChallengeMode_givenNotExactlyOneChallengeMode_returnsNullAndKeepsNoMappingVersion(array $lines): void
    {
        // Arrange
        $combatLogPath           = $this->writeCombatLog($lines);
        $parsedFilePaths         = new ArrayObject();
        $service                 = $this->createService($parsedFilePaths);
        $highestMappingVersionId = MappingVersion::query()->max('id');
        $createdMappingVersion   = null;

        try {
            // Act
            $createdMappingVersion = $service->createMappingVersionFromChallengeMode($combatLogPath, $this->getRetailGameVersion());

            // Assert
            $this->assertNull($createdMappingVersion);
            $this->assertSame($highestMappingVersionId, MappingVersion::query()->max('id'));
            $this->assertSame([$combatLogPath], $parsedFilePaths->getArrayCopy());
        } finally {
            unlink($combatLogPath);
            $createdMappingVersion?->delete();
        }
    }

    /**
     * @return array<string, array{array<int, string>}>
     */
    public static function createMappingVersionFromChallengeMode_givenNotExactlyOneChallengeMode_DataProvider(): array
    {
        return [
            'no challenge mode'   => [['5/15 21:20:10.941  ZONE_CHANGE,1516,"The Arcway",23']],
            'two challenge modes' => [[self::ARCWAY_CHALLENGE_MODE_START, self::ARCWAY_CHALLENGE_MODE_START]],
        ];
    }

    /**
     * @param ArrayObject<int, string> $parsedFilePaths
     */
    private function createService(ArrayObject $parsedFilePaths): CombatLogMappingVersionService
    {
        return $this->app->make(CombatLogMappingVersionService::class, [
            'combatLogService' => $this->createParseCountingCombatLogService($parsedFilePaths),
        ]);
    }

    private function getRetailGameVersion(): GameVersion
    {
        return GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
    }

    /**
     * @param array<int, string> $lines
     */
    private function writeCombatLog(array $lines): string
    {
        $combatLogPath = tempnam(sys_get_temp_dir(), 'combatlog_test_');
        file_put_contents($combatLogPath, implode(PHP_EOL, $lines) . PHP_EOL);

        return $combatLogPath;
    }
}
