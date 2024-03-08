<?php

namespace App\Service\MDT\Logging;

interface MDTMappingExportServiceLoggingInterface
{
    public function getDungeonEnemiesEnemiesWithoutNpcIdFound(array $enemyIds): void;
}
