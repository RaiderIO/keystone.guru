<?php

namespace App\Service\TimewalkingEvent\Logging;

interface TimewalkingEventServiceLoggingInterface
{
    public function getAffixGroupAtOverlappingTimewalkingEvent(
        int $timewalkingEventId,
        int $timewalkingEventExpansionId,
        int $expansionId,
    ): void;
}
