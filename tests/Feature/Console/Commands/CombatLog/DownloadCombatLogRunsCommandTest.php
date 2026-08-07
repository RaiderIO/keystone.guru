<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Console\Commands\CombatLog\DownloadCombatLogRunsCommand;
use App\Models\Season;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\Expectation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
final class DownloadCombatLogRunsCommandTest extends PublicTestCase
{
    private Season $season;

    private SeasonServiceInterface $seasonService;

    private string $outputDir;

    private string $outputDirPath;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->season        = Season::query()->firstOrFail();
        $this->outputDir     = sprintf('test-downloadruns-%s', uniqid());
        $this->outputDirPath = storage_path(sprintf('app/%s', $this->outputDir));

        $seasonServiceMock = Mockery::mock(SeasonServiceInterface::class);
        $seasonServiceMock->shouldReceive('getCurrentSeason')->andReturn($this->season);

        /** @var SeasonServiceInterface $seasonService */
        $seasonService       = $seasonServiceMock;
        $this->seasonService = $seasonService;
        app()->instance(SeasonServiceInterface::class, $seasonService);
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (File::isDirectory($this->outputDirPath)) {
            File::deleteDirectory($this->outputDirPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function handle_givenExplicitRunWithSegments_downloadsEachSegmentAndReportsSummary(): void
    {
        // Arrange
        $runId = 5001;

        $segments = [
            new CombatLogSegment(id: 1, type: 'part', downloadUrl: 'https://example.test/logs/part1.txt.gz?sig=abc'),
            new CombatLogSegment(id: 2, type: 'part', downloadUrl: 'https://example.test/logs/part2.txt.gz?sig=def'),
        ];

        $raiderIOServiceMock = Mockery::mock(RaiderIOApiServiceInterface::class);
        /** @var Expectation $expectation */
        $expectation = $raiderIOServiceMock->shouldReceive('getCombatLogSegmentsForRun');
        $expectation->once()
            ->with($this->season, $runId)
            ->andReturn(new CombatLogSegmentsResponse(sourceUserId: 1, segments: $segments));

        /** @var RaiderIOApiServiceInterface $raiderIOService */
        $raiderIOService = $raiderIOServiceMock;
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

        $command = $this->stubbedDownloadCommand($raiderIOService);
        app()->instance(DownloadCombatLogRunsCommand::class, $command);

        // Act
        // Note: expectsOutputToContain() matches one written line per assertion — the summary is a single
        // info() line, so all its fields are asserted together in one substring rather than split across
        // several expectsOutputToContain() calls (only the first would ever be matched against that line).
        $this->artisan('combatlog:downloadruns', [
            '--run'        => [$runId],
            '--output-dir' => $this->outputDir,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('runs_ok=1 runs_failed=0 files=2')
            ->expectsOutputToContain('combatlog:extractdata');

        // Assert
        $segment1Path = sprintf('%s/run_%d_segment_1.txt', $this->outputDirPath, $runId);
        $segment2Path = sprintf('%s/run_%d_segment_2.txt', $this->outputDirPath, $runId);

        $this->assertFileExists($segment1Path);
        $this->assertFileExists($segment2Path);
        $this->assertStringContainsString('fixture-combat-log-content', file_get_contents($segment1Path));
    }

    #[Test]
    public function handle_givenNullSegmentsResponse_warnsAndContinuesWithoutFailingTheCommand(): void
    {
        // Arrange
        $runId = 5002;

        $raiderIOServiceMock = Mockery::mock(RaiderIOApiServiceInterface::class);
        /** @var Expectation $expectation */
        $expectation = $raiderIOServiceMock->shouldReceive('getCombatLogSegmentsForRun');
        $expectation->once()
            ->with($this->season, $runId)
            ->andReturn(null);

        /** @var RaiderIOApiServiceInterface $raiderIOService */
        $raiderIOService = $raiderIOServiceMock;
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOService);

        $command = $this->stubbedDownloadCommand($raiderIOService);
        app()->instance(DownloadCombatLogRunsCommand::class, $command);

        // Act + Assert
        $this->artisan('combatlog:downloadruns', [
            '--run'        => [$runId],
            '--output-dir' => $this->outputDir,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('no combat log segments available')
            ->expectsOutputToContain('runs_ok=0 runs_failed=1 files=0');
    }

    /**
     * Builds the command with its network boundary (downloadSegmentToFile) stubbed to write a small local
     * fixture instead of performing a real curl request - the segment download URLs are presigned/short-lived
     * so faking HTTP end-to-end is impractical in a unit test. `createPartialMock()` disables the original
     * constructor, which leaves the underlying Symfony Command uninitialized (it needs the signature-driven
     * setup `parent::__construct()` does), so the constructor is invoked explicitly here instead.
     */
    private function stubbedDownloadCommand(RaiderIOApiServiceInterface $raiderIOService): DownloadCombatLogRunsCommand
    {
        $command = $this->getMockBuilderPublic(DownloadCombatLogRunsCommand::class)
            ->setConstructorArgs([$raiderIOService, $this->seasonService])
            ->onlyMethods(['downloadSegmentToFile'])
            ->getMock();

        $command->method('downloadSegmentToFile')->willReturnCallback(
            static fn(string $downloadUrl, string $filePath): bool => file_put_contents($filePath, "fixture-combat-log-content\n") !== false,
        );

        return $command;
    }
}
