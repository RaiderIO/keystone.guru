<?php

namespace App\Service\MDT\Logging;

interface MDTMappingVersionServiceLoggingInterface
{
    public function getFloorAccuracyNoCloneFound(int $mdtNpcId, int $cloneIndex): void;
}
