<?php

namespace App\Service\MDT\Logging;

use App\Logging\StructuredLogging;

class MDTMappingVersionServiceLogging extends StructuredLogging implements MDTMappingVersionServiceLoggingInterface
{
    public function getFloorAccuracyNoCloneFound(int $mdtNpcId, int $cloneIndex): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
