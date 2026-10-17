<?php

namespace Tests\Feature\App\Service\TimewalkingEvent;

use App\Models\Expansion;
use App\Models\Timewalking\TimewalkingEvent;
use App\Service\Season\SeasonServiceInterface;
use App\Service\TimewalkingEvent\Logging\TimewalkingEventServiceLoggingInterface;
use App\Service\TimewalkingEvent\TimewalkingEventService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('TimewalkingEventService')]
final class TimewalkingEventServiceTest extends PublicTestCase
{
    #[Test]
    public function getAffixGroupAt_givenActiveEventOfAnotherExpansion_logsOverlappingTimewalkingEvent(): void
    {
        $timewalkingEvent = null;

        try {
            // Arrange
            $eventExpansion   = Expansion::query()->where('key', Expansion::EXPANSION_LEGION)->firstOrFail();
            $otherExpansion   = Expansion::query()->where('key', Expansion::EXPANSION_BFA)->firstOrFail();
            $timewalkingEvent = TimewalkingEvent::forceCreate([
                'expansion_id'         => $eventExpansion->id,
                'key'                  => 'timewalking_event_service_test',
                'name'                 => 'Timewalking event service test',
                'start'                => Carbon::create(2018, 1, 2),
                'start_duration_weeks' => 1,
                'week_interval'        => 1,
            ]);

            $log = $this->createMock(TimewalkingEventServiceLoggingInterface::class);
            $log->expects($this->once())
                ->method('getAffixGroupAtOverlappingTimewalkingEvent')
                ->with($timewalkingEvent->id, $eventExpansion->id, $otherExpansion->id);

            $service = new TimewalkingEventService(app(SeasonServiceInterface::class), $log);

            // Act
            $result = $service->getAffixGroupAt($otherExpansion, Carbon::now());

            // Assert
            $this->assertNull($result);
        } finally {
            $timewalkingEvent?->delete();
        }
    }
}
