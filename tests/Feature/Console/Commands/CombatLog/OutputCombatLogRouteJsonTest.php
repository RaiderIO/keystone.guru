<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\GameServerRegion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;
use ZipArchive;

#[Group('CombatLog')]
#[Group('OutputCombatLogRouteJson')]
final class OutputCombatLogRouteJsonTest extends PublicTestCase
{
    /** A +14 The Underrot run - CHALLENGE_MODE_START,"The Underrot",1841,251,14,[9,124,6] */
    private const string COMBAT_LOG = 'tests/CombatLogs/WoWCombatLog-051023_160438_14_the-underrot.zip';

    private const int INSTANCE_ID = 1841;

    private const int CHALLENGE_MODE_ID = 251;

    private const int KEYSTONE_LEVEL = 14;

    /** @var array<int, int> All three affixes are logged inside a single bracketed parameter */
    private const array AFFIX_IDS = [9, 124, 6];

    private string $workingDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->workingDir = sprintf('%s/combatlog-route-json-%s', sys_get_temp_dir(), uniqid());
        mkdir($this->workingDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach (glob(sprintf('%s/*', $this->workingDir)) as $file) {
            unlink($file);
        }
        rmdir($this->workingDir);

        parent::tearDown();
    }

    #[Test]
    #[SlowTest]
    public function handle_givenAZippedCombatLog_writesARequestBodyDescribingTheRun(): void
    {
        // Arrange
        $combatLog = $this->copyCombatLog('the-underrot.zip');

        // Act
        $exitCode = Artisan::call('combatlog:outputcombatlogroutejson', ['filePath' => $combatLog]);
        $output   = Artisan::output();

        // Assert
        $this->assertSame(0, $exitCode, $output);

        $requestBody = $this->readRequestBody(sprintf('%s/the-underrot.json', $this->workingDir));

        $this->assertSame(self::CHALLENGE_MODE_ID, $requestBody['challengeMode']['challengeModeId']);
        $this->assertSame(self::KEYSTONE_LEVEL, $requestBody['challengeMode']['level']);
        // Every affix of the run, not just the first one that the single bracketed parameter starts with
        $this->assertSame(self::AFFIX_IDS, $requestBody['challengeMode']['affixes']);
        // The par time is the dungeon's timer, which the combat log does not carry - not the run's own duration
        $this->assertSame(
            $this->getTimerMaxSeconds($requestBody['settings']['mappingVersion']) * 1000,
            $requestBody['challengeMode']['parTimeMs'],
        );
        $this->assertNotSame(
            $requestBody['challengeMode']['durationMs'],
            $requestBody['challengeMode']['parTimeMs'],
        );
    }

    #[Test]
    #[SlowTest]
    public function handle_givenACombatLog_resolvesMetadataFromTheRunRatherThanHardcodingIt(): void
    {
        // Arrange
        $combatLog = $this->copyCombatLog('the-underrot.zip');

        // Act
        $exitCode = Artisan::call('combatlog:outputcombatlogroutejson', ['filePath' => $combatLog]);

        // Assert
        $this->assertSame(0, $exitCode, Artisan::output());

        $requestBody = $this->readRequestBody(sprintf('%s/the-underrot.json', $this->workingDir));
        $region      = GameServerRegion::where('short', GameServerRegion::EUROPE)->firstOrFail();

        $this->assertSame(self::INSTANCE_ID, $requestBody['metadata']['wowInstanceId']);
        $this->assertSame($region->id, $requestBody['metadata']['regionId']);
        // The season the run took place in, in the same season-<expansion>-<index> form the app uses elsewhere
        $this->assertMatchesRegularExpression('/^season-[a-z]+-\d+$/', $requestBody['metadata']['season']);
        $this->assertGreaterThan(0, $requestBody['metadata']['period']);
    }

    #[Test]
    #[SlowTest]
    public function handle_givenAnUnzippedCombatLog_writesARequestBodyAllTheSame(): void
    {
        // Arrange
        $combatLog = $this->extractCombatLog('the-underrot.txt');

        // Act
        $exitCode = Artisan::call('combatlog:outputcombatlogroutejson', ['filePath' => $combatLog]);
        $output   = Artisan::output();

        // Assert
        $this->assertSame(0, $exitCode, $output);

        $requestBody = $this->readRequestBody(sprintf('%s/the-underrot.json', $this->workingDir));

        $this->assertSame(self::CHALLENGE_MODE_ID, $requestBody['challengeMode']['challengeModeId']);
    }

    #[Test]
    public function handle_givenARequestBodyThatAlreadyExists_skipsTheCombatLog(): void
    {
        // Arrange
        $combatLog = $this->copyCombatLog('the-underrot.zip');
        file_put_contents(sprintf('%s/the-underrot.json', $this->workingDir), '{"kept":true}');

        // Act
        $exitCode = Artisan::call('combatlog:outputcombatlogroutejson', ['filePath' => $combatLog]);
        $output   = Artisan::output();

        // Assert
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('already generated .json', $output);
        $this->assertSame(['kept' => true], $this->readRequestBody(sprintf('%s/the-underrot.json', $this->workingDir)));
    }

    #[Test]
    public function handle_givenAFileThatIsNotACombatLog_reportsFailureWithoutThrowing(): void
    {
        // Arrange - a .txt the command now accepts, but which carries no CHALLENGE_MODE_START/END pair
        $notACombatLog = sprintf('%s/not-a-combat-log.txt', $this->workingDir);
        file_put_contents($notACombatLog, "5/9 21:34:59.959  ZONE_CHANGE,1841,\"The Underrot\",8\n");

        // Act
        $exitCode = Artisan::call('combatlog:outputcombatlogroutejson', ['filePath' => $notACombatLog]);
        $output   = Artisan::output();

        // Assert
        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('Unable to parse', $output);
        $this->assertFileDoesNotExist(sprintf('%s/not-a-combat-log.json', $this->workingDir));
    }

    private function copyCombatLog(string $fileName): string
    {
        $target = sprintf('%s/%s', $this->workingDir, $fileName);

        copy(base_path(self::COMBAT_LOG), $target);

        return $target;
    }

    private function extractCombatLog(string $fileName): string
    {
        $target = sprintf('%s/%s', $this->workingDir, $fileName);

        $zip = new ZipArchive();
        $zip->open(base_path(self::COMBAT_LOG));
        file_put_contents($target, $zip->getFromIndex(0));
        $zip->close();

        return $target;
    }

    /**
     * @return array<string, mixed>
     */
    private function readRequestBody(string $filePath): array
    {
        $this->assertFileExists($filePath);

        /** @var array<string, mixed> $requestBody */
        $requestBody = json_decode(file_get_contents($filePath), true);

        return $requestBody;
    }

    private function getTimerMaxSeconds(int $version): int
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::where('key', DungeonKey::THE_UNDERROT->value)->firstOrFail();

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::where('dungeon_id', $dungeon->id)->where('version', $version)->firstOrFail();

        return $mappingVersion->timer_max_seconds;
    }
}
