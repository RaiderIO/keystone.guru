<?php

namespace App\Service\MDT\Logging;

interface MDTMappingExportServiceLoggingInterface
{
    /**
     * @param array<int, int> $enemyIds
     */
    public function getDungeonEnemiesEnemiesWithoutNpcIdFound(array $enemyIds): void;
}
