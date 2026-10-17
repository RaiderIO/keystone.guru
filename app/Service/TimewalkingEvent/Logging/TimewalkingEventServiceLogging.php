<?php

namespace App\Service\TimewalkingEvent\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class TimewalkingEventServiceLogging extends StructuredLogging implements TimewalkingEventServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function getAffixGroupAtOverlappingTimewalkingEvent(
        int $timewalkingEventId,
        int $timewalkingEventExpansionId,
        int $expansionId,
    ): void {
        $this->error(__METHOD__, get_defined_vars());
    }
}
