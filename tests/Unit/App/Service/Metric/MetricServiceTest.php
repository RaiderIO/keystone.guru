<?php

namespace Tests\Unit\App\Service\Metric;

use App\Models\Metrics\Metric;
use App\Models\Team;
use App\Models\User;
use App\Service\Cache\CacheService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('MetricService')]
final class MetricServiceTest extends PublicTestCase
{
    /**
     * Scenario: Given a bunch of metrics - they should be grouped up properly before inserting them into the database.
     *
     * @param array<int, array<string, mixed>> $pendingMetrics
     * @param array<int, array<string, mixed>> $expectedResult
     */
    #[Test]
    #[DataProvider('groupMetrics_GivenGroupableMetrics_ShouldReturnGroupedMetrics_Provider')]
    public function groupMetrics_GivenGroupableMetrics_ShouldReturnGroupedMetrics(
        array $pendingMetrics,
        int   $seconds,
        array $expectedResult,
    ): void {
        // Arrange
        $metricService = ServiceFixtures::getMetricServiceMock($this);

        // Act
        $result = $metricService->groupMetrics($pendingMetrics, $seconds);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertCount(count($expectedResult), $result);

        for ($i = 0; $i < count($expectedResult); $i++) {
            $this->assertEquals($expectedResult[$i]['created_at'], $result[$i]['created_at']);
            $this->assertEquals($expectedResult[$i]['value'], $result[$i]['value']);
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function groupMetrics_GivenGroupableMetrics_ShouldReturnGroupedMetrics_Provider(): array
    {
        return [
            [
                [
                    self::createMetric(1, '2025-03-07 00:00:00'),
                    self::createMetric(3, '2025-03-07 00:00:15'),
                    self::createMetric(9, '2025-03-07 00:00:45'),
                    self::createMetric(2, '2025-03-07 00:01:10'),
                ],
                30,
                [
                    [
                        'created_at' => '2025-03-07 00:00:00',
                        'value'      => 4,
                    ],
                    [
                        'created_at' => '2025-03-07 00:00:45',
                        'value'      => 9,
                    ],
                    [
                        'created_at' => '2025-03-07 00:01:10',
                        'value'      => 2,
                    ],
                ],
            ],
            [
                [
                    self::createMetric(1, '2025-03-07 00:00:01'),
                    self::createMetric(3, '2025-03-07 00:00:01'),
                    self::createMetric(9, '2025-03-07 00:00:01'),
                    self::createMetric(2, '2025-03-07 00:00:02'),
                ],
                1,
                [
                    [
                        'created_at' => '2025-03-07 00:00:01',
                        'value'      => 13,
                    ],
                    [
                        'created_at' => '2025-03-07 00:00:02',
                        'value'      => 2,
                    ],
                ],
            ],
            // Within one time bucket, a metric only joins a group with the same model, category and tag
            'same bucket, different model, category or tag' => [
                [
                    self::createMetric(1, '2025-03-07 00:00:01'),
                    self::createMetric(2, '2025-03-07 00:00:02', modelId: 2),
                    self::createMetric(4, '2025-03-07 00:00:03', modelClass: Team::class),
                    self::createMetric(8, '2025-03-07 00:00:04', category: Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY),
                    self::createMetric(16, '2025-03-07 00:00:05', tag: 'GET /api/route'),
                    self::createMetric(32, '2025-03-07 00:00:06'),
                ],
                30,
                [
                    [
                        'created_at' => '2025-03-07 00:00:01',
                        'value'      => 33,
                    ],
                    [
                        'created_at' => '2025-03-07 00:00:02',
                        'value'      => 2,
                    ],
                    [
                        'created_at' => '2025-03-07 00:00:03',
                        'value'      => 4,
                    ],
                    [
                        'created_at' => '2025-03-07 00:00:04',
                        'value'      => 8,
                    ],
                    [
                        'created_at' => '2025-03-07 00:00:05',
                        'value'      => 16,
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function flushPendingMetrics_givenPendingMetrics_returnsThemAndEmptiesTheList(): void
    {
        // Arrange
        $pendingMetrics = [
            self::createMetric(1, '2025-03-07 00:00:01'),
            self::createMetric(3, '2025-03-07 00:00:02'),
        ];
        $cacheService = $this->createPendingMetricsCache($pendingMetrics);
        $cacheService->expects($this->once())->method('set')->with('metrics:pending', []);

        $metricService = ServiceFixtures::getMetricServiceMock($this, cacheService: $cacheService);

        // Act
        $result = $metricService->flushPendingMetrics();

        // Assert
        $this->assertSame($pendingMetrics, $result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function flushPendingMetrics_givenGroupBySeconds_returnsThemGrouped(): void
    {
        // Arrange
        $cacheService = $this->createPendingMetricsCache([
            self::createMetric(1, '2025-03-07 00:00:01'),
            self::createMetric(3, '2025-03-07 00:00:02'),
        ]);
        $cacheService->expects($this->once())->method('set')->with('metrics:pending', []);

        $metricService = ServiceFixtures::getMetricServiceMock($this, cacheService: $cacheService);

        // Act
        $result = $metricService->flushPendingMetrics(30);

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame(4, $result[0]['value']);
    }

    /**
     * @param  array<int, array<string, mixed>> $pendingMetrics
     * @throws Exception
     */
    private function createPendingMetricsCache(array $pendingMetrics): MockObject&CacheService
    {
        $cacheService = ServiceFixtures::getCacheServiceMock($this, ['lock', 'get', 'set']);
        $cacheService->method('lock')->willReturnCallback(static fn(string $key, callable $callable): mixed => $callable());
        $cacheService->method('get')->with('metrics:pending')->willReturn($pendingMetrics);

        return $cacheService;
    }

    /**
     * @return array<string, mixed>
     */
    private static function createMetric(
        int    $value,
        string $createdAt,
        int    $modelId = 1,
        string $modelClass = User::class,
        int    $category = Metric::CATEGORY_API_CALL,
        string $tag = 'GET /api/user',
    ): array {
        return [
            'model_id'    => $modelId,
            'model_class' => $modelClass,
            'category'    => $category,
            'tag'         => $tag,
            'value'       => $value,
            'created_at'  => $createdAt,
            // We don't care for a separate updatedAt, so just copy the createdAt
            'updated_at' => $createdAt,
        ];
    }
}
