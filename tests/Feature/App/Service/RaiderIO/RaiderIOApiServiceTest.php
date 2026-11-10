<?php

namespace Tests\Feature\App\Service\RaiderIO;

use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\Season;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Dtos\SearchAdvancedRunsFilter;
use App\Service\RaiderIO\Exceptions\InvalidApiResponseException;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\RaiderIO\RaiderIOApiService;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Sleep;
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
    private const int RUN_ID            = 37830910;
    private const string ACCESS_KEY     = 'test-access-key';
    private const int CHALLENGE_MODE_ID = 501;
    private const string HEATMAP_TOKEN  = 'eyJtYXhUaW1lckZyYWN0aW9uIjo5OTl9';
    private const string GRID_URL           = 'https://raider.test/heatmaps/grid';
    private const string CONTINENT_GRID_URL = 'https://raider.test/heatmaps/open-world-grid';

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
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRun_givenSegmentsNotYetAvailable_logsTheUrlWithoutTheAccessKey(): void
    {
        // Arrange
        $loggedUrl = null;
        $this->log->expects($this->once())
            ->method('getCombatLogSegmentsForRunNotYetAvailable')
            ->willReturnCallback(static function (int $runId, string $url) use (&$loggedUrl): void {
                $loggedUrl = $url;
            });
        $service = $this->makeService(fn(): string => json_encode([
            'statusCode' => Http::NOT_FOUND,
            'error'      => 'Not Found',
            'message'    => 'No combat log segments found for this run',
        ]));

        // Act
        $service->getCombatLogSegmentsForRun($this->makeSeason(), self::RUN_ID);

        // Assert
        $this->assertIsString($loggedUrl);
        $this->assertStringNotContainsString(self::ACCESS_KEY, $loggedUrl);
        $this->assertStringContainsString(sprintf('keystone_run_id=%d', self::RUN_ID), $loggedUrl);
        $this->assertStringContainsString('access_key=[Filtered]', $loggedUrl);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRun_givenInvalidResponse_logsTheUrlWithoutTheAccessKey(): void
    {
        // Arrange
        $loggedUrl = null;
        $this->log->expects($this->once())
            ->method('getCombatLogSegmentsForRunInvalidResponse')
            ->willReturnCallback(static function (int $runId, string $url) use (&$loggedUrl): void {
                $loggedUrl = $url;
            });
        $service = $this->makeService(fn(): string => 'not json');

        // Act
        $service->getCombatLogSegmentsForRun($this->makeSeason(), self::RUN_ID);

        // Assert
        $this->assertIsString($loggedUrl);
        $this->assertStringNotContainsString(self::ACCESS_KEY, $loggedUrl);
        $this->assertStringContainsString('access_key=[Filtered]', $loggedUrl);
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

    /**
     * Raider.IO numbers the Alliance 0, and array_filter() on the query parameters is the obvious
     * place for a zero to silently disappear.
     *
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenAllianceFaction_filtersOnRaiderIOFactionZero(): void
    {
        // Arrange
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return json_encode(['total' => 0, 'matches' => []]);
        });

        $alliance = Faction::query()->where('key', Faction::FACTION_ALLIANCE)->firstOrFail();

        // Act
        $service->searchAdvancedRuns($this->makeSearchFilter(2, 6, $alliance));

        // Assert
        $this->assertStringContainsString('faction[0][eq]=0', urldecode((string)$capturedUrl));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenHordeFaction_filtersOnRaiderIOFactionOne(): void
    {
        // Arrange
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return json_encode(['total' => 0, 'matches' => []]);
        });

        $horde = Faction::query()->where('key', Faction::FACTION_HORDE)->firstOrFail();

        // Act
        $service->searchAdvancedRuns($this->makeSearchFilter(2, 6, $horde));

        // Assert
        $this->assertStringContainsString('faction[0][eq]=1', urldecode((string)$capturedUrl));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenNoFaction_omitsTheFactionFilter(): void
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
        $this->assertStringNotContainsString('faction[0][eq]', urldecode((string)$capturedUrl));
    }

    /**
     * A cross faction group carries no faction at all, which must survive as null rather than
     * collapse into the Alliance's 0.
     *
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenCrossFactionRun_readsItsFactionAsNull(): void
    {
        // Arrange
        $service = $this->makeService(fn(): string => json_encode([
            'total'   => ['value' => 2],
            'matches' => [
                ['data' => $this->makeRunData(1, faction: 0)],
                ['data' => $this->makeRunData(2, faction: null)],
            ],
        ]));

        // Act
        $result = $service->searchAdvancedRuns($this->makeSearchFilter(2, 6));

        // Assert
        $this->assertSame(0, $result->runs[0]->faction);
        $this->assertNull($result->runs[1]->faction);
    }

    /**
     * Cloudflare occasionally answers with a transient gateway-timeout HTML page instead of JSON -
     * a retry a moment later succeeds, so it must not surface as a failure to the caller.
     *
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenTransientInvalidResponseThenValidResponse_retriesAndSucceeds(): void
    {
        // Arrange
        Sleep::fake();

        $attempt = 0;
        $service = $this->makeService(function () use (&$attempt): string {
            $attempt++;

            if ($attempt === 1) {
                return '<html>504 Gateway Timeout</html>';
            }

            return json_encode(['total' => 0, 'matches' => []]);
        });

        $this->log->expects($this->never())->method('searchAdvancedRunsInvalidResponse');

        // Act
        $result = $service->searchAdvancedRuns($this->makeSearchFilter(2, 6));

        // Assert
        $this->assertSame(2, $attempt);
        $this->assertSame(0, $result->total);
    }

    /**
     * A gateway timeout that outlasts every retry is a real failure - the null total tells the
     * caller so, and it is only logged once the retries are exhausted.
     *
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRuns_givenPersistentInvalidResponse_exhaustsRetriesAndLogsOnce(): void
    {
        // Arrange
        Sleep::fake();

        $attempts = 0;
        $service  = $this->makeService(function () use (&$attempts): string {
            $attempts++;

            return '<html>504 Gateway Timeout</html>';
        });

        $this->log->expects($this->once())->method('searchAdvancedRunsInvalidResponse');

        // Act
        $result = $service->searchAdvancedRuns($this->makeSearchFilter(2, 6));

        // Assert
        $this->assertSame(3, $attempts);
        $this->assertNull($result->total);
        $this->assertEmpty($result->runs);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getHeatmapData_givenDungeonWithoutSeason_resolvesTheSeasonAndRequestsTheDungeonGrid(): void
    {
        // Arrange
        config([
            'keystoneguru.heatmap.api.grid_url'           => self::GRID_URL,
            'keystoneguru.heatmap.api.continent_grid_url' => self::CONTINENT_GRID_URL,
        ]);
        $dungeon = Dungeon::where('key', DungeonKey::THE_STONEVAULT->value)->firstOrFail();

        /** @var MockObject&SeasonServiceInterface $seasonService */
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->expects($this->once())
            ->method('getMostRecentSeasonForDungeon')
            ->willReturn($this->makeSeason());

        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return $this->makeEmptyGridResponse();
        }, $seasonService);

        // Act
        $service->getHeatmapData(new HeatmapDataFilter($dungeon, CombatLogEventEventType::PlayerDeath, CombatLogEventDataType::PlayerPosition));

        // Assert
        $this->assertStringStartsWith(sprintf('%s?', self::GRID_URL), $capturedUrl);
        $this->assertStringContainsString(sprintf('challengeModeId=%d', $dungeon->challenge_mode_id), $capturedUrl);
        $this->assertStringContainsString('season=season-mn-1', $capturedUrl);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getHeatmapData_givenContinent_skipsSeasonResolutionAndRequestsTheContinentGrid(): void
    {
        // Arrange
        config([
            'keystoneguru.heatmap.api.grid_url'           => self::GRID_URL,
            'keystoneguru.heatmap.api.continent_grid_url' => self::CONTINENT_GRID_URL,
        ]);
        $dungeon = Dungeon::where('key', DungeonKey::KALIMDOR->value)->firstOrFail();

        /** @var MockObject&SeasonServiceInterface $seasonService */
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->expects($this->never())
            ->method('getMostRecentSeasonForDungeon')
            ->willReturn($this->makeSeason());

        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return $this->makeEmptyGridResponse();
        }, $seasonService);

        // Act
        $service->getHeatmapData(new HeatmapDataFilter($dungeon, CombatLogEventEventType::PlayerDeath, CombatLogEventDataType::PlayerPosition));

        // Assert
        $this->assertStringStartsWith(sprintf('%s?', self::CONTINENT_GRID_URL), $capturedUrl);
        $this->assertStringContainsString(sprintf('%s=1', HeatmapDataFilter::CONTINENT_IDENTIFIER_PARAMETER), $capturedUrl);
        $this->assertStringNotContainsString('season=', $capturedUrl);
        $this->assertStringNotContainsString('challengeModeId=', $capturedUrl);
    }

    private function makeEmptyGridResponse(): string
    {
        return json_encode([
            'gridsByFloor'     => [],
            'numRuns'          => 0,
            'maxSamplesInGrid' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeRunData(int $id, ?int $faction): array
    {
        return [
            'id'              => $id,
            'challengeModeId' => 399,
            'dungeonZoneId'   => 12916,
            'memberSpecIds'   => [66, 70],
            'mythicLevel'     => 14,
            'affixes'         => [9, 10],
            'faction'         => $faction,
        ];
    }

    private function makeSearchFilter(int $mythicLevelMin, ?int $mythicLevelMax, ?Faction $faction = null): SearchAdvancedRunsFilter
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
            faction:         $faction,
        );
    }

    /**
     * @param  callable(string): string $curlCallback
     * @throws Exception
     */
    /**
     * @throws Exception
     */
    #[Test]
    public function getHeatmapData_givenValidFilters_sendsEachFilterAsItsOwnQueryParameter(): void
    {
        // Arrange
        config([
            'keystoneguru.heatmap.api.min_required_sample_factor_default' => 0.0005,
            'keystoneguru.heatmap.api.floors_as_array'                    => false,
        ]);
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return 'not json';
        });
        $heatmapDataFilter = $this->makeHeatmapDataFilter()
            ->setKeyLevelMin(10)
            ->setExcludeAffixIds('3,4')
            ->setExcludeClassIds('2')
            ->setExcludeSpecIds('5,6')
            ->setExcludePlayerDeathSpecIds('7')
            ->setExcludePlayerDeathClassIds('8')
            ->setToken(self::HEATMAP_TOKEN);

        // Act
        $this->getHeatmapDataIgnoringInvalidResponse($service, $heatmapDataFilter);

        // Assert
        $this->assertStringStartsWith('https://raider.io/api/v1/live-tracking/heatmaps/grid?', $capturedUrl);
        $this->assertSame([
            'challengeModeId'            => (string)self::CHALLENGE_MODE_ID,
            'type'                       => CombatLogEventEventType::NpcDeath->value,
            'dataType'                   => CombatLogEventDataType::PlayerPosition->value,
            'minMythicLevel'             => '10',
            'excludeAffixIds'            => '3,4',
            'excludeClassIds'            => '2',
            'excludeSpecIds'             => '5,6',
            'excludePlayerDeathSpecIds'  => '7',
            'excludePlayerDeathClassIds' => '8',
            'token'                      => self::HEATMAP_TOKEN,
            'season'                     => 'season-mn-1',
            'minRequiredSamplesFactor'   => '0.0005',
        ], $this->parseQuery($capturedUrl));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getHeatmapData_givenFilterValueWithQuerySeparators_encodesItAsASingleParameter(): void
    {
        // Arrange
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return 'not json';
        });
        $heatmapDataFilter = $this->makeHeatmapDataFilter()
            ->setExcludeAffixIds('3&challengeModeId=1&injected=yes')
            ->setToken('abc&season=season-tww-1');

        // Act
        $this->getHeatmapDataIgnoringInvalidResponse($service, $heatmapDataFilter);

        // Assert
        $query = $this->parseQuery($capturedUrl);
        $this->assertSame('3&challengeModeId=1&injected=yes', $query['excludeAffixIds']);
        $this->assertSame('abc&season=season-tww-1', $query['token']);
        $this->assertSame((string)self::CHALLENGE_MODE_ID, $query['challengeModeId']);
        $this->assertSame('season-mn-1', $query['season']);
        $this->assertArrayNotHasKey('injected', $query);
    }

    private function makeHeatmapDataFilter(): HeatmapDataFilter
    {
        $dungeon                    = new Dungeon();
        $dungeon->name              = 'Test dungeon';
        $dungeon->challenge_mode_id = self::CHALLENGE_MODE_ID;

        return new HeatmapDataFilter($dungeon, CombatLogEventEventType::NpcDeath, CombatLogEventDataType::PlayerPosition)
            ->setIncludePlayerSpellIds(collect())
            ->setSeason('season-mn-1');
    }

    private function getHeatmapDataIgnoringInvalidResponse(RaiderIOApiService $service, HeatmapDataFilter $heatmapDataFilter): void
    {
        try {
            $service->getHeatmapData($heatmapDataFilter);
        } catch (InvalidApiResponseException) {
            // The stubbed transport answers with garbage; only the outgoing URL matters here
        }
    }

    /**
     * @return array<string, string>
     */
    private function parseQuery(string $url): array
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }

    private function makeService(callable $curlCallback, ?SeasonServiceInterface $seasonService = null): RaiderIOApiService&MockObject
    {
        /** @var MockObject&CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = $this->createMockPublic(CoordinatesServiceInterface::class);
        $seasonService ??= $this->createMockPublic(SeasonServiceInterface::class);
        $service       = $this->getMockBuilder(RaiderIOApiService::class)
            ->setConstructorArgs([$coordinatesService, $seasonService, $this->log])
            ->onlyMethods(['curlGet'])
            ->getMock();

        $service->method('curlGet')->willReturnCallback($curlCallback);

        return $service;
    }

    private function makeSeason(): Season
    {
        $expansion      = new Expansion();
        $expansion->key = Expansion::EXPANSION_MIDNIGHT;

        $season        = new Season();
        $season->index = 1;
        $season->setRelation('expansion', $expansion);

        return $season;
    }
}
