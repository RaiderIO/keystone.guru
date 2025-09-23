<?php

namespace App\Service\Dungeon;

use App\Models\Dungeon;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;

class DungeonService implements DungeonServiceInterface
{
    public function __construct(
        private readonly DungeonServiceLoggingInterface $log,
    ) {
    }

    public function importInstanceIdsFromCsv(string $filePath): bool
    {
        try {
            $this->log->importInstanceIdsFromCsvStart($filePath);

            $csvContents = file_get_contents($filePath);

            if ($csvContents === false) {
                $this->log->importInstanceIdsFromCsvUnableToParseFile();

                return false;
            }

            $csv = str_getcsv_assoc($csvContents);

            $headers = array_shift($csv);

            $indexId    = array_search('ID', $headers);
            $indexMapId = array_search('MapID', $headers);

            $dungeons = Dungeon::all()->keyBy('map_id');

            foreach ($csv as $index => $row) {
                $instanceId = $row[$indexId];

                if (empty($instanceId) || !is_numeric($instanceId)) {
                    $this->log->importInstanceIdsFromCsvInstanceIdEmpty($index);

                    continue;
                }

                /** @var Dungeon|null $dungeon */
                $dungeon = $dungeons->get($row[$indexMapId]);
                if ($dungeon === null) {
                    // Don't log - there's going to be MANY dungeons we don't know about

                    continue;
                }

                if ($dungeon->instance_id === null && $dungeon->update([
                    'instance_id' => $instanceId,
                ])) {
                    $this->log->importInstanceIdsFromCsvUpdatedZoneId($dungeon->key, $instanceId);
                }
            }
        } finally {
            $this->log->importInstanceIdsFromCsvEnd();
        }

        return true;
    }
}
