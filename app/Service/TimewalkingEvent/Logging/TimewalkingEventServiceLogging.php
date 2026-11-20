<?php

namespace App\Service\TimewalkingEvent\Logging;

use App\Logging\StructuredLogging;

class TimewalkingEventServiceLogging extends StructuredLogging implements TimewalkingEventServiceLoggingInterface
{
    public function getAffixGroupAtOverlappingTimewalkingEvent(
        int $timewalkingEventId,
        int $timewalkingEventExpansionId,
        int $expansionId,
    ): void {
        $this->error(__METHOD__, get_defined_vars());
    }
}
