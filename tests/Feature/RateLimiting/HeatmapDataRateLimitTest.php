<?php

namespace Tests\Feature\RateLimiting;

use App;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Providers\AppServiceProvider;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use ReflectionProperty;
use Tests\Fixtures\ServiceFixtures;
use Tests\Fixtures\Traits\CreatesCombatLogEvent;
use Tests\TestCases\PublicTestCase;

/**
 * Every call is an outbound Raider.IO API request made on behalf of a caller that needs no session, so it is bounded
 * for the callers that are not exempt. The request runs as a guest on purpose: the seeded admin is exempt from every
 * limiter. Under test the service is the local mock, which is stubbed here so nothing reaches OpenSearch either.
 */
#[Group('RateLimiting')]
#[Group('HeatmapController')]
final class HeatmapDataRateLimitTest extends PublicTestCase
{
    use CreatesCombatLogEvent;

    /**
     * @throws Exception
     */
    #[Test]
    public function getData_givenTooManyRequestsFromAGuest_isRateLimited(): void
    {
        // Arrange - one request per hour is enough to prove the throttle is applied to the route
        $this->overrideHttpRateLimit(1);
        $dungeon   = Dungeon::firstWhere('key', DungeonKey::THE_STONEVAULT->value);
        $eventType = CombatLogEventEventType::NpcDeath;
        $dataType  = CombatLogEventDataType::PlayerPosition;
        $this->stubGridAggregation($dungeon, $eventType, $dataType);
        $url = route('ajax.heatmap.data', [
            'type'      => $eventType->value,
            'dataType'  => $dataType->value,
            'dungeonId' => $dungeon->id,
        ]);

        try {
            // Act
            $firstResponse  = $this->get($url, ['X-Requested-With' => 'XMLHttpRequest']);
            $secondResponse = $this->get($url, ['X-Requested-With' => 'XMLHttpRequest']);

            // Assert
            $firstResponse->assertOk();
            $secondResponse->assertStatus(429);
        } finally {
            $this->overrideHttpRateLimit(null);
        }
    }

    /**
     * @throws Exception
     */
    private function stubGridAggregation(Dungeon $dungeon, CombatLogEventEventType $eventType, CombatLogEventDataType $dataType): void
    {
        $combatLogEventFilter = new CombatLogEventFilter(
            App::make(SeasonServiceInterface::class),
            $dungeon,
            $eventType,
            $dataType,
        );

        $coordinatesService    = ServiceFixtures::getCoordinatesServiceMock($this);
        $combatLogEventService = ServiceFixtures::getCombatLogEventServiceMock(
            $this,
            ['getGridAggregation'],
            $coordinatesService,
        );
        $combatLogEventService->method('getGridAggregation')
            ->willReturn(new CombatLogEventGridAggregationResult(
                $coordinatesService,
                $combatLogEventFilter,
                $this->createGridAggregationResult($dungeon, 1),
                1,
            ));

        app()->bind(CombatLogEventServiceInterface::class, fn() => $combatLogEventService);
    }

    private function overrideHttpRateLimit(?int $limit): void
    {
        new ReflectionProperty(AppServiceProvider::class, 'rateLimitOverrideHttp')->setValue(null, $limit);
    }
}
