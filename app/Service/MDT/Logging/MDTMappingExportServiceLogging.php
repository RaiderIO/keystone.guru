<?php

namespace App\Service\MDT\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class MDTMappingExportServiceLogging extends StructuredLogging implements MDTMappingExportServiceLoggingInterface
{
    use InteractsWithRollbar;

    /**
     * @param array<int, int> $enemyIds
     */
    public function getDungeonEnemiesEnemiesWithoutNpcIdFound(array $enemyIds): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
