<?php

namespace Tests\Feature\App\Service\RaiderIO;

use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsResponse;
use App\Service\RaiderIO\RaiderIOKeystoneGuruApiService;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('RaiderIOKeystoneGuruApiService')]
final class RaiderIOKeystoneGuruApiServiceTest extends PublicTestCase
{
    private RaiderIOKeystoneGuruApiService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var MockObject&SeasonServiceInterface $seasonService */
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);

        /** @var MockObject&SeasonAffixGroupServiceInterface $seasonAffixGroupService */
        $seasonAffixGroupService = $this->createMockPublic(SeasonAffixGroupServiceInterface::class);

        /** @var MockObject&CombatLogEventServiceInterface $combatLogEventService */
        $combatLogEventService = $this->createMockPublic(CombatLogEventServiceInterface::class);

        $this->service = new RaiderIOKeystoneGuruApiService(
            $seasonService,
            $seasonAffixGroupService,
            $combatLogEventService,
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
    public function getCombatLogSegmentsForRun_givenZipFilesInS3_returnsCombatLogSegmentsResponseWithOneSegment(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');
        Storage::disk('s3_combat_logs')->put('run1.zip', 'content');
        Storage::disk('s3_combat_logs')->put('run2.zip', 'content');

        // Act
        $result = $this->service->getCombatLogSegmentsForRun(new \App\Models\Season(), 1);

        // Assert
        $this->assertInstanceOf(CombatLogSegmentsResponse::class, $result);
        $this->assertCount(1, $result->segments);
        $this->assertSame(1, $result->segments[0]->id);
        $this->assertNotEmpty($result->segments[0]->downloadUrl);
    }

    #[Test]
    public function getCombatLogSegmentsForRun_givenNoFilesInS3_returnsNull(): void
    {
        // Arrange
        Storage::fake('s3_combat_logs');

        // Act
        $result = $this->service->getCombatLogSegmentsForRun(new \App\Models\Season(), 1);

        // Assert
        $this->assertNull($result);
    }
}
