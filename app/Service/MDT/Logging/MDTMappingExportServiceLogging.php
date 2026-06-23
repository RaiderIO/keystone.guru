<?php

namespace App\Service\MDT\Logging;

use App\Logging\RollbarStructuredLogging;

class MDTMappingExportServiceLogging extends RollbarStructuredLogging implements MDTMappingExportServiceLoggingInterface
{
    /**
     * @param array<int, int> $enemyIds
     */
    public function getDungeonEnemiesEnemiesWithoutNpcIdFound(array $enemyIds): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
