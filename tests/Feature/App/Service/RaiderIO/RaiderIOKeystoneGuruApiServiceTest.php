<?php

namespace Tests\Feature\App\Service\RaiderIO;

use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\RaiderIO\Dtos\CombatLogDownloadResponse;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\RaiderIOKeystoneGuruApiService;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('RaiderIOKeystoneGuruApiService')]
final class RaiderIOKeystoneGuruApiServiceTest extends PublicTestCase
{
    private RaiderIOKeystoneGuruApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RaiderIOKeystoneGuruApiService(
            $this->createMockPublic(SeasonServiceInterface::class),
            $this->createMockPublic(SeasonAffixGroupServiceInterface::class),
            $this->createMockPublic(CombatLogEventServiceInterface::class),
        );
    }

    private function makeFilter(): SearchAdvancedRunsFilter
    {
        return new SearchAdvancedRunsFilter(
            dungeon:         null,
            season:          new \App\Models\Season(),
            specs:           collect([]),
            completedAtFrom: Carbon::now(),
            completedAtTo:   null,
            mythicLevelMin:  0,
            limit:           10,
            offset:          0,
        );
    }

    #[Test]
    public function searchAdvancedRuns_givenZipFilesInS3_returnsRunsMatchingFileCount(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');
        Storage::disk('s3_combat_logs')->put('run1.zip', 'content');
        Storage::disk('s3_combat_logs')->put('run2.zip', 'content');
        Storage::disk('s3_combat_logs')->put('run3.zip', 'content');

        // Act
        $result = $this->service->searchAdvancedRuns($this->makeFilter());

        // Assert
        $this->assertInstanceOf(SearchAdvancedRunsResponse::class, $result);
        $this->assertCount(3, $result->runs);
        $this->assertSame(3, $result->total);
        $this->assertSame(1, $result->runs[0]->id);
        $this->assertSame(2, $result->runs[1]->id);
        $this->assertSame(3, $result->runs[2]->id);
    }

    #[Test]
    public function searchAdvancedRuns_givenNonZipFilesInS3_returnsEmptyResponse(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');
        Storage::disk('s3_combat_logs')->put('run1.log', 'content');
        Storage::disk('s3_combat_logs')->put('run2.txt', 'content');

        // Act
        $result = $this->service->searchAdvancedRuns($this->makeFilter());

        // Assert
        $this->assertCount(0, $result->runs);
        $this->assertSame(0, $result->total);
    }

    #[Test]
    public function searchAdvancedRuns_givenNoFilesInS3_returnsEmptyResponse(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');

        // Act
        $result = $this->service->searchAdvancedRuns($this->makeFilter());

        // Assert
        $this->assertCount(0, $result->runs);
        $this->assertSame(0, $result->total);
    }

    #[Test]
    public function getCombatLogForRun_givenZipFilesInS3_returnsCombatLogDownloadResponse(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');
        Storage::disk('s3_combat_logs')->put('run1.zip', 'content');
        Storage::disk('s3_combat_logs')->put('run2.zip', 'content');

        // Act
        $result = $this->service->getCombatLogForRun(1);

        // Assert
        $this->assertInstanceOf(CombatLogDownloadResponse::class, $result);
        $this->assertSame('s3_combat_logs', $result->diskName);
        $this->assertTrue($result->isFile);
        $this->assertTrue(str_ends_with($result->s3Path, '.zip'));
    }

    #[Test]
    public function getCombatLogForRun_givenNoFilesInS3_returnsNull(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');

        // Act
        $result = $this->service->getCombatLogForRun(1);

        // Assert
        $this->assertNull($result);
    }
}
