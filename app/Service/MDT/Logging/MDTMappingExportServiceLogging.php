<?php

namespace App\Service\MDT\Logging;

use App\Logging\StructuredLogging;

class MDTMappingExportServiceLogging extends StructuredLogging implements MDTMappingExportServiceLoggingInterface
{
    public function getDungeonEnemiesEnemiesWithoutNpcIdFound(array $enemyIds): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
