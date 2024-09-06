<?php

namespace App\Service\Dungeon\Logging;

use App\Logging\RollbarStructuredLogging;

class DungeonServiceLogging extends RollbarStructuredLogging implements DungeonServiceLoggingInterface
{
    public function importInstanceIdsFromCsvStart(string $filePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function importInstanceIdsFromCsvUnableToParseFile(): void
    {
        $this->error(__METHOD__);
    }

    public function importInstanceIdsFromCsvInstanceIdEmpty(int $index): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function importInstanceIdsFromCsvUpdatedZoneId(string $dungeonKey, int $instanceId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importInstanceIdsFromCsvEnd(): void
    {
        $this->end(__METHOD__);
    }

}
