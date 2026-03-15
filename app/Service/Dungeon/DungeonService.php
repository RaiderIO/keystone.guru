<?php

namespace App\Service\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;

class DungeonService implements DungeonServiceInterface
{
    private const string DUNGEON_CONTEXT_COOKIE = 'dungeon_context';

    public function __construct(
        private readonly CookieServiceInterface         $cookieService,
        private readonly SeasonServiceInterface         $seasonService,
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

    public function setDungeonContext(Dungeon $dungeon, ?User $user = null): void
    {
        $user?->update(['dungeon_id' => $dungeon->id]);

        // Unit tests and artisan commands don't like this
        // Nor do we want to keep setting the cookie if it hasn't changed
        if (!app()->runningInConsole() && ($_COOKIE[self::DUNGEON_CONTEXT_COOKIE] ?? null) !== $dungeon->key) {
            // Set the new cookie
            $this->cookieService->setCookie(self::DUNGEON_CONTEXT_COOKIE, $dungeon->key);
        }
    }

    public function getDungeonContext(?User $user = null): Dungeon
    {
        $dungeon = null;
        if ($user === null) {
            if (isset($_COOKIE[self::DUNGEON_CONTEXT_COOKIE])) {
                $dungeon = Dungeon::firstWhere('key', $_COOKIE[self::DUNGEON_CONTEXT_COOKIE]) ?? null;
            }
        } else {
            $dungeon = $user->dungeon;
        }

        if ($dungeon === null) {
            // Resort to finding a default dungeon of sorts
            $gameVersion   = GameVersion::getUserOrDefaultGameVersion();
            $currentSeason = $this->seasonService->getCurrentSeason($gameVersion->expansion);

            $dungeon = $currentSeason?->dungeons()->first() ?? Dungeon::active()->firstWhere('expansion_id', $gameVersion->expansion_id);

            $this->setDungeonContext($dungeon, $user);
        }

        return $dungeon;
    }
}
