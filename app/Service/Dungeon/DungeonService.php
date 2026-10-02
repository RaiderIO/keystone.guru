<?php

namespace App\Service\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\PageViewCountRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DungeonService implements DungeonServiceInterface
{
    private const string DUNGEON_CONTEXT_COOKIE = 'dungeon_context';

    private const int POPULAR_DUNGEON_FRACTION = 4;

    public function __construct(
        private readonly CookieServiceInterface         $cookieService,
        private readonly SeasonServiceInterface         $seasonService,
        private readonly DungeonServiceLoggingInterface $log,
        private readonly GameVersionServiceInterface    $gameVersionService,
        private readonly DungeonRepositoryInterface       $dungeonRepository,
        private readonly PageViewCountRepositoryInterface $pageViewCountRepository,
        private readonly CacheServiceInterface            $cacheService,
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

            $dungeon = ($currentSeason === null ? null : $this->getSeasonDungeons($currentSeason)->first())
                ?? Dungeon::active()->firstWhere('expansion_id', $gameVersion->expansion_id);

            $this->setDungeonContext($dungeon, $user);
        }

        return $dungeon;
    }

    public function getDungeonsForGameVersion(?GameVersion $gameVersion = null): Collection
    {
        // Resort to finding a default dungeon of sorts
        $gameVersion ??= GameVersion::getUserOrDefaultGameVersion();

        // Only ever the current season - a season is seeded weeks ahead of its start for review and QA
        // (#3739), and until it starts its dungeons are not playable, so they must not push the current
        // season's dungeons out of the selector (#3761). The upcoming season gets its own entry point
        // instead: the "next season" card that HeaderComposer adds to the dungeon context bar.
        $currentSeason = $this->seasonService->getCurrentSeason($gameVersion->expansion);

        return $currentSeason === null ? $this->getGameVersionDungeons($gameVersion) : $this->getSeasonDungeons($currentSeason);
    }

    public function getPopularDungeonIds(Collection $dungeons): Collection
    {
        // The counts only change when page-views:prune aggregates another day, so every page can share one read.
        /** @var Collection<int, int> $viewsPerDungeon */
        $viewsPerDungeon = $this->cacheService->remember(
            'dungeon_views',
            fn() => $this->pageViewCountRepository->getViewsPerDungeon(
                Carbon::today()->subDays(config('keystoneguru.page_views.popular_dungeons_days')),
            ),
            config('keystoneguru.cache.dungeon_views.ttl'),
        );

        return $dungeons
            ->map(static fn(Dungeon $dungeon): array => [$dungeon->id, $viewsPerDungeon->get($dungeon->id, 0)])
            ->filter(static fn(array $dungeonViews): bool => $dungeonViews[1] > 0)
            ->sortByDesc(static fn(array $dungeonViews): int => $dungeonViews[1])
            ->take((int)ceil($dungeons->count() / self::POPULAR_DUNGEON_FRACTION))
            ->map(static fn(array $dungeonViews): int => $dungeonViews[0])
            ->values();
    }

    /**
     * Every active dungeon and raid that has a mapping for the game version, ordered by selector group and then
     * by translated name. Names are compared transliterated to ASCII: the app image ships without ext-intl, so
     * there is no Collator, and a byte compare would sort accented initials after Z.
     *
     * @return Collection<int, Dungeon>
     */
    private function getGameVersionDungeons(GameVersion $gameVersion): Collection
    {
        return $this->dungeonRepository->getActiveForGameVersion($gameVersion)
            ->sortBy([
                static fn(Dungeon $a, Dungeon $b) => $a->getSelectorGroup()->sortOrder() <=> $b->getSelectorGroup()->sortOrder(),
                static fn(Dungeon $a, Dungeon $b) => strcasecmp(Str::ascii(__($a->name)), Str::ascii(__($b->name))),
            ])
            ->values();
    }

    /**
     * SeasonService hands out its seasons with `dungeons` eager-loaded, which makes this free on every page
     * the header renders. Never loadMissing() the relation instead: those seasons live in a service-level
     * cache, and a relation memoised onto them outlives the request (a stale dungeon list once had a route
     * created against the wrong dungeon). A season handed over without it is queried, never lazy-loaded.
     *
     * @return Collection<int, Dungeon>
     */
    private function getSeasonDungeons(Season $season): Collection
    {
        return $season->relationLoaded('dungeons') ? $season->dungeons : $season->dungeons()->get();
    }
}
