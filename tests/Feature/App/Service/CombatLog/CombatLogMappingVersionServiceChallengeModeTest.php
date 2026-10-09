<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogMappingVersionService;
use App\Service\CombatLog\Exceptions\DungeonHasNoNpcsException;
use App\Service\CombatLog\Logging\CombatLogMappingVersionServiceLoggingInterface;
use ArrayObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpclessCombatLogDungeon;
use Tests\Fixtures\Traits\CreatesParseCountingCombatLogService;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('CombatLog')]
final class CombatLogMappingVersionServiceChallengeModeTest extends PublicTestCase
{
    use CreatesNpclessCombatLogDungeon;
    use CreatesParseCountingCombatLogService;

    private const string ARCWAY_CHALLENGE_MODE_START = '5/15 21:20:10.941  CHALLENGE_MODE_START,"The Arcway",1841,209,2,[9]';

    private const int NPCLESS_MAP_ID = 987651;

    private const int NPCLESS_CHALLENGE_MODE_ID = 987652;

    private const string NPCLESS_CHALLENGE_MODE_START = '5/15 21:20:10.941  CHALLENGE_MODE_START,"Test Dungeon",987651,987652,2,[9]';

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

    #[Test]
    public function createMappingVersionFromChallengeMode_givenThreeChallengeModes_logsTheChallengeModeCount(): void
    {
        // Arrange
        $combatLogPath = $this->writeCombatLog([
            self::ARCWAY_CHALLENGE_MODE_START,
            self::ARCWAY_CHALLENGE_MODE_START,
            self::ARCWAY_CHALLENGE_MODE_START,
        ]);
        $log = $this->createMockPublic(CombatLogMappingVersionServiceLoggingInterface::class);
        $log->expects($this->once())
            ->method('createMappingVersionFromChallengeModeMultipleChallengeModesFound')
            ->with(3);
        $service = $this->app->make(CombatLogMappingVersionService::class, [
            'combatLogService' => $this->createParseCountingCombatLogService(new ArrayObject()),
            'log'              => $log,
        ]);
        $createdMappingVersion = null;

        try {
            // Act
            $createdMappingVersion = $service->createMappingVersionFromChallengeMode($combatLogPath, $this->getRetailGameVersion());

            // Assert
            $this->assertNull($createdMappingVersion);
        } finally {
            unlink($combatLogPath);
            $createdMappingVersion?->delete();
        }
    }

    #[Test]
    public function createMappingVersionFromChallengeMode_givenTwoChallengeModesInADungeonWithoutNpcs_returnsNullAndKeepsNoMappingVersion(): void
    {
        // Arrange
        $dungeon                 = null;
        $combatLogPath           = null;
        $createdMappingVersion   = null;
        $highestMappingVersionId = MappingVersion::query()->max('id');

        try {
            $dungeon       = $this->createChallengeModeDungeonWithoutNpcs();
            $combatLogPath = $this->writeCombatLog([
                self::NPCLESS_CHALLENGE_MODE_START,
                self::NPCLESS_CHALLENGE_MODE_START,
            ]);
            $service         = $this->createService(new ArrayObject());
            $thrownException = null;

            // Act
            try {
                $createdMappingVersion = $service->createMappingVersionFromChallengeMode($combatLogPath, $this->getRetailGameVersion());
            } catch (DungeonHasNoNpcsException $exception) {
                $thrownException = $exception;
            }

            // Assert
            $this->assertNull($thrownException);
            $this->assertNull($createdMappingVersion);
            $this->assertSame($highestMappingVersionId, MappingVersion::query()->max('id'));
        } finally {
            if ($combatLogPath !== null) {
                unlink($combatLogPath);
            }
            $createdMappingVersion?->delete();
            if ($dungeon !== null) {
                Floor::query()->where('dungeon_id', $dungeon->id)->delete();
            }
            $this->deleteDungeon($dungeon);
        }
    }

    #[Test]
    public function createMappingVersionFromChallengeMode_givenOneChallengeModeInADungeonWithoutNpcs_throwsDungeonHasNoNpcsException(): void
    {
        // Arrange
        $dungeon                 = null;
        $combatLogPath           = null;
        $thrownException         = null;
        $highestMappingVersionId = MappingVersion::query()->max('id');

        try {
            $dungeon       = $this->createChallengeModeDungeonWithoutNpcs();
            $combatLogPath = $this->writeCombatLog([self::NPCLESS_CHALLENGE_MODE_START]);
            $service       = $this->createService(new ArrayObject());

            // Act
            try {
                $service->createMappingVersionFromChallengeMode($combatLogPath, $this->getRetailGameVersion());
            } catch (DungeonHasNoNpcsException $exception) {
                $thrownException = $exception;
            }

            // Assert
            $this->assertInstanceOf(DungeonHasNoNpcsException::class, $thrownException);
            $this->assertSame($highestMappingVersionId, MappingVersion::query()->max('id'));
        } finally {
            if ($combatLogPath !== null) {
                unlink($combatLogPath);
            }
            if ($dungeon !== null) {
                Floor::query()->where('dungeon_id', $dungeon->id)->delete();
            }
            $this->deleteDungeon($dungeon);
        }
    }

    private function createChallengeModeDungeonWithoutNpcs(): Dungeon
    {
        $dungeon = $this->createDungeonWithoutNpcs(self::NPCLESS_MAP_ID, sprintf('test_npcless_cm_%d', random_int(1, PHP_INT_MAX)));
        Dungeon::query()->whereKey($dungeon->id)->update(['challenge_mode_id' => self::NPCLESS_CHALLENGE_MODE_ID]);
        $this->createConfiguredDefaultFloor($dungeon);

        return $dungeon;
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
