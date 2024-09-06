<?php

namespace App\Service\Dungeon\Logging;

interface DungeonServiceLoggingInterface
{
    public function importInstanceIdsFromCsvStart(string $filePath): void;

    public function importInstanceIdsFromCsvUnableToParseFile(): void;

    public function importInstanceIdsFromCsvInstanceIdEmpty(int $index): void;

    public function importInstanceIdsFromCsvUpdatedZoneId(string $dungeonKey, int $instanceId): void;

    public function importInstanceIdsFromCsvEnd(): void;
}
