<?php

namespace App\Service\Metric;

use App\Models\Metrics\Metric;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

final class MetricServiceTest extends PublicTestCase
{
    /**
     * Scenario: Given a bunch of metrics - they should be grouped up properly before inserting them into the database.
     */
    #[Test]
    #[DataProvider('groupMetrics_GivenGroupableMetrics_ShouldReturnGroupedMetrics_Provider')]
    #[Group('MetricService')]
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
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(count($expectedResult), $result);

        for ($i = 0; $i < count($expectedResult); $i++) {
            $this->assertEquals($expectedResult[$i]['created_at'], $result[$i]['created_at']);
            $this->assertEquals($expectedResult[$i]['value'], $result[$i]['value']);
        }
    }

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
        ];
    }

    private static function createMetric(
        int    $value,
        string $createdAt,
        int    $modelId = 1,
        string $modelClass = User::class,
        string $category = Metric::CATEGORY_API_CALL,
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
