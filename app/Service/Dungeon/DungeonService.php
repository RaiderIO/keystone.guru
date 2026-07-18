<?php

namespace App\Service\Dungeon;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DungeonService implements DungeonServiceInterface
{
    private const string DUNGEON_CONTEXT_COOKIE = 'dungeon_context';

    public function __construct(
        private readonly CookieServiceInterface         $cookieService,
        private readonly SeasonServiceInterface         $seasonService,
        private readonly DungeonServiceLoggingInterface $log,
        private readonly GameVersionServiceInterface    $gameVersionService,
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
                    $this->log->importInstanceIdsFromCsvUpdatedZoneId($dungeon->key, (int)$instanceId);
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
        $user?->load('dungeon');

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
            // Resort to finding a default dungeon of sorts - use the service so the game_version cookie is respected for guests
            $gameVersion   = $this->gameVersionService->getGameVersion($user);
            $currentSeason = $this->seasonService->getCurrentSeason($gameVersion->expansion);

            $dungeon = $currentSeason?->dungeons()->first() ?? Dungeon::active()->firstWhere('expansion_id', $gameVersion->expansion_id);

            $this->setDungeonContext($dungeon, $user);
        }

        return $dungeon;
    }

    public function getDungeonsForGameVersion(?GameVersion $gameVersion = null): Collection
    {
        // Resort to finding a default dungeon of sorts
        $gameVersion ??= GameVersion::getUserOrDefaultGameVersion();

        $currentSeason = $this->seasonService->getCurrentSeason($gameVersion->expansion);
        $nextSeason    = $currentSeason === null ? null : $this->seasonService->getNextSeason($currentSeason);

        return ($nextSeason ?? $currentSeason)?->dungeons ?? $gameVersion->expansion->dungeons; // @phpstan-ignore nullsafe.neverNull
    }

    public function getDungeonOverviewStats(Dungeon $dungeon, GameVersion $gameVersion): array
    {
        $mappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);

        return Cache::remember(
            sprintf('dungeon.overview.stats.%d.%d', $dungeon->id, $gameVersion->id),
            now()->addHour(),
            static function () use ($dungeon, $mappingVersion): array {
                $pullCount  = 0;
                $enemyCount = 0;

                if ($mappingVersion !== null) {
                    $pullCount = $dungeon->enemyPacks()
                        ->where('enemy_packs.mapping_version_id', $mappingVersion->id)
                        ->count();

                    $enemyCount = $dungeon->enemies()
                        ->where('enemies.mapping_version_id', $mappingVersion->id)
                        ->where(static function (Builder $query) {
                            $query->whereNull('enemies.seasonal_type')
                                ->orWhere('enemies.seasonal_type', '!=', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER);
                        })
                        ->count();
                }

                return [
                    'npc'                  => $dungeon->npcs()->count(),
                    'spell'                => $dungeon->spells()->count(),
                    'pull_count'           => $pullCount,
                    'avg_enemies_per_pull' => $pullCount > 0 ? round($enemyCount / $pullCount, 1) : 0.0,
                ];
            },
        );
    }
}
