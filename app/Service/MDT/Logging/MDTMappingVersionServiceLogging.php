<?php

namespace App\Service\MDT\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class MDTMappingVersionServiceLogging extends StructuredLogging implements MDTMappingVersionServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function getFloorAccuracyNoCloneFound(int $mdtNpcId, int $cloneIndex): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
