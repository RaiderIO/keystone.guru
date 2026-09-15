<?php

namespace Tests\Feature\Service\Metric;

use App\Models\Metrics\Metric;
use App\Service\Metric\MetricServiceInterface;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Metric')]
final class MetricServiceTest extends PublicTestCase
{
    private const string TEST_MODEL_CLASS = 'Tests\\Feature\\Service\\Metric\\MetricServiceTest';

    private const int TEST_MODEL_ID = 999999001;

    private const string TAG_A = 'test-tag-a';

    private const string TAG_B = 'test-tag-b';

    private const string BACKDATED_AT = '2020-01-01 00:00:00';

    #[Test]
    public function aggregateMetrics_givenNewGroup_insertsItsCount(): void
    {
        // Arrange
        $metricService = $this->app->make(MetricServiceInterface::class);

        try {
            $this->storeTestMetric($metricService, self::TAG_A);
            $this->storeTestMetric($metricService, self::TAG_A);

            // Act
            $metricService->aggregateMetrics();

            // Assert
            $aggregation = $this->getAggregation(self::TAG_A);
            $this->assertNotNull($aggregation);
            $this->assertSame(2, (int)$aggregation->value);
        } finally {
            $this->deleteTestRows();
        }
    }

    #[Test]
    public function aggregateMetrics_givenNoNewMetrics_leavesAggregationRowUntouched(): void
    {
        // Arrange
        $metricService = $this->app->make(MetricServiceInterface::class);

        try {
            $this->storeTestMetric($metricService, self::TAG_A);
            $this->storeTestMetric($metricService, self::TAG_A);
            $metricService->aggregateMetrics();
            $this->backdateAggregations();

            // Act
            $metricService->aggregateMetrics();

            // Assert
            $aggregation = $this->getAggregation(self::TAG_A);
            $this->assertSame(2, (int)$aggregation->value);
            $this->assertSame(self::BACKDATED_AT, $aggregation->updated_at);
        } finally {
            $this->deleteTestRows();
        }
    }

    #[Test]
    public function aggregateMetrics_givenNewMetricInOneGroup_updatesOnlyThatGroup(): void
    {
        // Arrange
        $metricService = $this->app->make(MetricServiceInterface::class);

        try {
            $this->storeTestMetric($metricService, self::TAG_A);
            $this->storeTestMetric($metricService, self::TAG_B);
            $metricService->aggregateMetrics();
            $this->backdateAggregations();
            $this->storeTestMetric($metricService, self::TAG_A);

            // Act
            $metricService->aggregateMetrics();

            // Assert
            $changedAggregation = $this->getAggregation(self::TAG_A);
            $this->assertSame(2, (int)$changedAggregation->value);
            $this->assertNotSame(self::BACKDATED_AT, $changedAggregation->updated_at);

            $unchangedAggregation = $this->getAggregation(self::TAG_B);
            $this->assertSame(1, (int)$unchangedAggregation->value);
            $this->assertSame(self::BACKDATED_AT, $unchangedAggregation->updated_at);
        } finally {
            $this->deleteTestRows();
        }
    }

    private function storeTestMetric(MetricServiceInterface $metricService, string $tag): void
    {
        $metricService->storeMetric(
            self::TEST_MODEL_ID,
            self::TEST_MODEL_CLASS,
            Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY,
            $tag,
            1,
        );
    }

    /**
     * Read through the query builder so the model cache cannot serve a stale aggregation.
     */
    private function getAggregation(string $tag): ?object
    {
        return DB::table('metric_aggregations')
            ->where('model_id', self::TEST_MODEL_ID)
            ->where('model_class', self::TEST_MODEL_CLASS)
            ->where('category', Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY)
            ->where('tag', $tag)
            ->first();
    }

    private function backdateAggregations(): void
    {
        DB::table('metric_aggregations')
            ->where('model_class', self::TEST_MODEL_CLASS)
            ->update(['updated_at' => self::BACKDATED_AT]);
    }

    private function deleteTestRows(): void
    {
        DB::table('metrics')->where('model_class', self::TEST_MODEL_CLASS)->delete();
        DB::table('metric_aggregations')->where('model_class', self::TEST_MODEL_CLASS)->delete();
    }
}
