<?php

namespace Tests\Feature\App\Service\RaiderIO;

use App\Models\Expansion;
use App\Models\Season;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\RaiderIO\RaiderIOApiService;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Teapot\StatusCode\Http;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('RaiderIOApiService')]
final class RaiderIOApiServiceTest extends PublicTestCase
{
    private const int RUN_ID        = 37830910;
    private const string ACCESS_KEY = 'test-access-key';

    /** @var MockObject&RaiderIOApiServiceLoggingInterface */
    private MockObject $log;

    /**
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['keystoneguru.raiderio.api_key' => self::ACCESS_KEY]);

        $this->log = $this->createMockPublic(RaiderIOApiServiceLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRun_givenValidResponse_buildsQueryUrlAndParsesSegments(): void
    {
        // Arrange
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return json_encode([
                'sourceUserId' => 1348248,
                'segments'     => [
                    ['id' => 1, 'type' => 'trash', 'downloadUrl' => 'https://s3/01_trash.txt.gz'],
                    ['id' => 2, 'type' => 'boss', 'downloadUrl' => 'https://s3/02_boss.txt.gz'],
                ],
            ]);
        });

        // Act
        $result = $service->getCombatLogSegmentsForRun($this->makeSeason(), self::RUN_ID);

        // Assert
        $this->assertStringContainsString('season=season-mn-1', $capturedUrl);
        $this->assertStringContainsString(sprintf('keystone_run_id=%d', self::RUN_ID), $capturedUrl);
        $this->assertStringContainsString(sprintf('access_key=%s', self::ACCESS_KEY), $capturedUrl);

        $this->assertInstanceOf(CombatLogSegmentsResponse::class, $result);
        $this->assertSame(1348248, $result->sourceUserId);
        $this->assertCount(2, $result->segments);
        $this->assertSame(1, $result->segments[0]->id);
        $this->assertSame('trash', $result->segments[0]->type);
        $this->assertSame('https://s3/01_trash.txt.gz', $result->segments[0]->downloadUrl);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRun_givenInvalidResponse_returnsNull(): void
    {
        // Arrange
        $this->log->expects($this->once())->method('getCombatLogSegmentsForRunInvalidResponse');
        $this->log->expects($this->never())->method('getCombatLogSegmentsForRunNotYetAvailable');
        $service = $this->makeService(fn(): string => 'not json');

        // Act
        $result = $service->getCombatLogSegmentsForRun($this->makeSeason(), self::RUN_ID);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Guards #3918: a run's segments simply not having been uploaded to Raider.IO yet (the API
     * returns a 404 with this specific shape) is an expected, recurring state - it must be logged
     * distinctly from a genuinely malformed response instead of paging Sentry as an error.
     *
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRun_givenSegmentsNotYetAvailable_logsDistinctlyFromInvalidResponse(): void
    {
        // Arrange
        $this->log->expects($this->once())->method('getCombatLogSegmentsForRunNotYetAvailable');
        $this->log->expects($this->never())->method('getCombatLogSegmentsForRunInvalidResponse');
        $service = $this->makeService(fn(): string => json_encode([
            'statusCode' => Http::NOT_FOUND,
            'error'      => 'Not Found',
            'message'    => 'No combat log segments found for this run',
        ]));

        // Act
        $result = $service->getCombatLogSegmentsForRun($this->makeSeason(), self::RUN_ID);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Guards #3918: the upstream API is hapi-style, so a genuine route-not-found (wrong path,
     * unrecognized season) yields the same status code with a generic message - that is a broken
     * integration and must still be logged at error level rather than swallowed as "not yet
     * uploaded".
     *
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRun_givenGenericNotFoundResponse_logsAsInvalidResponse(): void
    {
        // Arrange
        $this->log->expects($this->once())->method('getCombatLogSegmentsForRunInvalidResponse');
        $this->log->expects($this->never())->method('getCombatLogSegmentsForRunNotYetAvailable');
        $service = $this->makeService(fn(): string => json_encode([
            'statusCode' => Http::NOT_FOUND,
            'error'      => 'Not Found',
            'message'    => 'Not Found',
        ]));

        // Act
        $result = $service->getCombatLogSegmentsForRun($this->makeSeason(), self::RUN_ID);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenBandedFilter_boundsMythicLevelOnBothSides(): void
    {
        // Arrange
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return json_encode(['total' => ['value' => 1231], 'matches' => []]);
        });

        // Act
        $result = $service->searchAdvancedRuns($this->makeSearchFilter(2, 6));

        // Assert
        $decodedUrl = urldecode((string)$capturedUrl);
        $this->assertStringContainsString('mythicLevel[0][gte]=2', $decodedUrl);
        $this->assertStringContainsString('mythicLevel[0][lte]=6', $decodedUrl);
        $this->assertSame(1231, $result->total);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenOpenEndedFilter_omitsUpperBound(): void
    {
        // Arrange
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return json_encode(['total' => ['value' => 542], 'matches' => []]);
        });

        // Act
        $service->searchAdvancedRuns($this->makeSearchFilter(22, null));

        // Assert
        $decodedUrl = urldecode((string)$capturedUrl);
        $this->assertStringContainsString('mythicLevel[0][gte]=22', $decodedUrl);
        $this->assertStringNotContainsString('mythicLevel[0][lte]', $decodedUrl);
    }

    /**
     * The upstream API returns `"total": {"value": n}` for a non-empty result set, but a plain
     * scalar `"total": 0` when nothing matches. Reading only the object shape reports "unknown"
     * for an empty band, which is the one answer the top band probe has to be able to trust.
     *
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenEmptyResultSet_readsScalarTotalAsZero(): void
    {
        // Arrange
        $service = $this->makeService(fn(): string => json_encode(['total' => 0, 'matches' => []]));

        // Act
        $result = $service->searchAdvancedRuns($this->makeSearchFilter(26, 26));

        // Assert
        $this->assertSame(0, $result->total);
        $this->assertEmpty($result->runs);
    }

    /**
     * Sorting on anything stable hands every repeated poll of the same filter the exact same page,
     * by which time every run on it has already been parsed.
     *
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenAnyFilter_sortsByRecency(): void
    {
        // Arrange
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return json_encode(['total' => 0, 'matches' => []]);
        });

        // Act
        $service->searchAdvancedRuns($this->makeSearchFilter(2, 6));

        // Assert
        $this->assertStringContainsString('sort[completedAt]=desc', urldecode((string)$capturedUrl));
    }

    private function makeSearchFilter(int $mythicLevelMin, ?int $mythicLevelMax): SearchAdvancedRunsFilter
    {
        return new SearchAdvancedRunsFilter(
            dungeon:         null,
            season:          $this->makeSeason(),
            specs:           collect(),
            completedAtFrom: Carbon::now()->subDay(),
            completedAtTo:   null,
            mythicLevelMin:  $mythicLevelMin,
            mythicLevelMax:  $mythicLevelMax,
            limit:           100,
            offset:          0,
        );
    }

    /**
     * @param  callable(string): string $curlCallback
     * @throws Exception
     */
    private function makeService(callable $curlCallback): RaiderIOApiService&MockObject
    {
        /** @var MockObject&CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = $this->createMockPublic(CoordinatesServiceInterface::class);
        /** @var MockObject&SeasonServiceInterface $seasonService */
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        /** @var MockObject&SeasonAffixGroupServiceInterface $seasonAffixGroupService */
        $seasonAffixGroupService = $this->createMockPublic(SeasonAffixGroupServiceInterface::class);

        $service = $this->getMockBuilder(RaiderIOApiService::class)
            ->setConstructorArgs([$coordinatesService, $seasonService, $seasonAffixGroupService, $this->log])
            ->onlyMethods(['curlGet'])
            ->getMock();

        $service->method('curlGet')->willReturnCallback($curlCallback);

        return $service;
    }

    private function makeSeason(): Season
    {
        $expansion            = new Expansion();
        $expansion->shortname = Expansion::EXPANSION_MIDNIGHT;

        $season        = new Season();
        $season->index = 1;
        $season->setRelation('expansion', $expansion);

        return $season;
    }
}
