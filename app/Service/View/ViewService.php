<?php

namespace App\Service\View;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\Release;
use App\Models\ReleaseChangelogCategory;
use App\Models\RouteAttribute;
use App\Models\Season;
use App\Models\Spell\Spell;
use App\Models\User;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\Expansion\ExpansionData;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Str;

class ViewService implements ViewServiceInterface
{
    use RemembersToFile;

    private const array VIEW_VARIABLES_URL_WHITELIST = [
        // search actually renders views back to the user which we need
        '/ajax/dungeonroute/search',
        '/ajax/search',
        // Renders views through Ajax
        '/ajax/view',
    ];

    private const array VIEW_VARIABLES_URL_BLACKLIST = [
        '/ajax/',
        '/api/',
        '/benchmark',
    ];

    private string $release;

    /** @var bool When true, every cached getter bypasses the cache read and recomputes (used when warming). */
    private bool $forceRefresh = false;

    public function __construct(
        private readonly CacheServiceInterface              $cacheService,
        private readonly ExpansionServiceInterface          $expansionService,
        private readonly SeasonAffixGroupServiceInterface   $seasonAffixGroupService,
        private readonly AffixGroupEaseTierServiceInterface $easeTierService,
    ) {
        // Load the release version from the file, this is used to cache view variables
        // We want to cache view variables per release so we don't mix up view variables between releases
        $this->release = file_get_contents(base_path('version')) ?: 'unknown';
    }

    public function isLocal(): bool
    {
        return config('app.env') === 'local';
    }

    public function isMapping(): bool
    {
        return config('app.env') === 'mapping';
    }

    public function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    /**
     * @return Collection<int, DungeonRoute>
     */
    public function getDemoRoutes(): Collection
    {
        return $this->cachedGlobal('demo_routes', static fn() => DungeonRoute::where('demo', true)
            ->join('mapping_versions', 'mapping_versions.id', '=', 'dungeon_routes.mapping_version_id')
            ->where('mapping_versions.game_version_id', GameVersion::getDefaultGameVersion()->id)
            ->without(['thumbnails'])
            ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD_WITH_LINK])
            ->orderBy('dungeon_routes.dungeon_id')
            ->get());
    }

    /**
     * @return Collection<int, Dungeon>
     */
    public function getDemoRouteDungeons(): Collection
    {
        return $this->cachedGlobal('demo_route_dungeons', fn() => Dungeon::whereIn('id', $this->getDemoRoutes()->pluck(['dungeon_id']))->get());
    }

    /**
     * @return Collection<int, string>
     */
    public function getDemoRouteMapping(): Collection
    {
        return $this->cachedGlobal('demo_route_mapping', fn() => $this->getDemoRouteDungeons()
            ->mapWithKeys(fn(Dungeon $dungeon) => [$dungeon->id => $this->getDemoRoutes()->where('dungeon_id', $dungeon->id)->first()->public_key]));
    }

    public function getLatestRelease(): Release
    {
        return $this->cachedGlobal('latest_release', static function (): Release {
            /** @var Release $latestRelease */
            $latestRelease = Release::latest()->first();

            return $latestRelease;
        });
    }

    public function getLatestReleaseSpotlight(): ?Release
    {
        return $this->cachedGlobal('latest_release_spotlight', static fn() => Release::where('spotlight', true)
            ->whereDate(
                'created_at',
                '>',
                Carbon::now()->subDays(config('keystoneguru.releases.spotlight_show_days', 7))->toDateTimeString(),
            )->first());
    }

    /**
     * @return array{version: string, revision: string, nameAndVersion: string}
     */
    public function getAppVersionInfo(): array
    {
        return $this->cachedGlobal('app_version_info', function (): array {
            $appRevision = trim(file_get_contents(base_path('version')));
            $version     = $this->getLatestRelease()->version;

            return [
                'version'        => $version,
                'revision'       => $appRevision,
                'nameAndVersion' => sprintf(
                    '%s® © 2018-%d %s - %s (%s), MDT %s',
                    config('app.name'),
                    date('Y'),
                    'RaiderIO, Inc.',
                    $version,
                    substr($appRevision, 0, 6),
                    config('keystoneguru.mdt.version'),
                ),
            ];
        });
    }

    public function getUserCount(): int
    {
        return $this->cachedGlobal('user_count', static fn() => (int)(User::count() / 1000) * 1000);
    }

    /**
     * @return Collection<int, GameServerRegion>
     */
    public function getAllRegions(): Collection
    {
        return $this->cachedGlobal('all_regions', static fn() => GameServerRegion::all());
    }

    /**
     * @return Collection<int, Faction>
     */
    public function getAllFactions(): Collection
    {
        return $this->cachedGlobal('all_factions', static fn() => Faction::all());
    }

    /**
     * @return Collection<int, ReleaseChangelogCategory>
     */
    public function getReleaseChangelogCategories(): Collection
    {
        return $this->cachedGlobal('release_changelog_categories', static fn() => ReleaseChangelogCategory::all());
    }

    /**
     * @return Collection<int, CharacterClassSpecialization>
     */
    public function getCharacterClassSpecializations(): Collection
    {
        return $this->cachedGlobal('character_class_specializations', static fn() => CharacterClassSpecialization::with('class')->orderBy('name')->get());
    }

    /**
     * @return Collection<int, CharacterClass>
     */
    public function getCharacterClasses(): Collection
    {
        return $this->cachedGlobal('character_classes', static fn() => CharacterClass::with('specializations')->orderBy('name')->get());
    }

    /**
     * @return Collection<int, CharacterRace>
     */
    public function getCharacterRacesClasses(): Collection
    {
        // @TODO Classes are loaded fully inside $raceClasses, this shouldn't happen. Find a way to exclude them
        return $this->cachedGlobal('character_races_classes', static fn() => CharacterRace::with(['classes:character_classes.id'])->orderBy('faction_id')->get());
    }

    /**
     * @return Collection<int, Affix>
     */
    public function getAllAffixes(): Collection
    {
        return $this->cachedGlobal('all_affixes', static fn() => Affix::all());
    }

    /**
     * @return Collection<int, RouteAttribute>
     */
    public function getAllRouteAttributes(): Collection
    {
        return $this->cachedGlobal('all_route_attributes', static fn() => RouteAttribute::all());
    }

    /**
     * @return Collection<int, PublishedState>
     */
    public function getAllPublishedStates(): Collection
    {
        return $this->cachedGlobal('all_published_states', static fn() => PublishedState::all());
    }

    /**
     * @return Collection<string, Collection<int, Spell>>
     */
    public function getSelectableSpellsByCategory(): Collection
    {
        return $this->cachedGlobal('selectable_spells_by_category', static fn() => Spell::where('selectable', true)
            ->orderByRaw("CASE WHEN category = 'spells.category.general' THEN 0 ELSE 1 END")
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category')
            // Do NOT localize the keys, they are used in the frontend which can have a different locale
            ->mapWithKeys(static fn(Collection $spells, string $key) => [$key => $spells]));
    }

    /**
     * @return Collection<int, GameVersion>
     */
    public function getAllGameVersions(): Collection
    {
        return $this->cachedGlobal('all_game_versions', static fn() => GameVersion::active()->get());
    }

    /**
     * @return Collection<int, Expansion>
     */
    public function getActiveExpansions(): Collection
    {
        return $this->cachedGlobal('active_expansions', static fn() => Expansion::active()->with([
            'dungeons',
            'raids',
        ])->orderBy('released_at', 'desc')->get());
    }

    /**
     * @return Collection<int, Expansion>
     */
    public function getAllExpansions(): Collection
    {
        return $this->cachedGlobal('all_expansions', static fn() => Expansion::with([
            'dungeons',
            'raids',
        ])->orderBy('released_at', 'desc')->get());
    }

    /**
     * @return Collection<int, Dungeon>
     */
    public function getDungeonsByExpansionIdDesc(): Collection
    {
        return $this->cachedGlobal('dungeons_by_expansion_id_desc', fn() => $this->dungeonsByExpansionQuery()
            ->where('dungeons.raid', false)
            ->get());
    }

    /**
     * @return Collection<int, Dungeon>
     */
    public function getRaidsByExpansionIdDesc(): Collection
    {
        return $this->cachedGlobal('raids_by_expansion_id_desc', fn() => $this->dungeonsByExpansionQuery()
            ->where('dungeons.raid', true)
            ->get());
    }

    /**
     * @return Collection<int, Dungeon>
     */
    public function getActiveDungeonsByExpansionIdDesc(): Collection
    {
        return $this->cachedGlobal('active_dungeons_by_expansion_id_desc', fn() => $this->dungeonsByExpansionQuery()
            ->where('dungeons.raid', false)
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->get());
    }

    /**
     * @return Collection<int, Dungeon>
     */
    public function getActiveRaidsByExpansionIdDesc(): Collection
    {
        return $this->cachedGlobal('active_raids_by_expansion_id_desc', fn() => $this->dungeonsByExpansionQuery()
            ->where('dungeons.raid', true)
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->get());
    }

    public function getSiegeOfBoralus(): ?Dungeon
    {
        return $this->cachedGlobal('siege_of_boralus', static fn() => Dungeon::where('key', Dungeon::DUNGEON_SIEGE_OF_BORALUS)->first());
    }

    /**
     * @return Collection<int, Collection<int, mixed>>
     */
    public function getAffixGroupEaseTiersByAffixGroup(): Collection
    {
        return $this->cachedGlobal('affix_group_ease_tiers_by_affix_group', fn() => $this->easeTierService->getTiers()->groupBy([
            'affix_group_id',
            'dungeon_id',
        ]));
    }

    /**
     * @return Collection<int, string>
     */
    public function getDungeonExpansions(): Collection
    {
        return $this->cachedGlobal('dungeon_expansions', fn() => $this->getDungeonsByExpansionIdDesc()
            ->pluck('expansion_id', 'id')->mapWithKeys(fn(
                int $expansionId,
                int $dungeonId,
            ) => [$dungeonId => $this->getAllExpansions()->where('id', $expansionId)->first()->shortname]));
    }

    /**
     * @return Collection<int, Dungeon>
     */
    public function getAllSpeedrunDungeons(): Collection
    {
        return $this->cachedGlobal('all_speedrun_dungeons', static fn() => Dungeon::where('speedrun_enabled', true)
            ->with('dungeonSpeedrunDifficulties')
            ->get());
    }

    /**
     * Warms every cached global getter by recomputing and writing it to cache, bypassing any cached read.
     */
    public function warmGlobalCaches(): void
    {
        $this->forceRefresh = true;

        $this->getDemoRoutes();
        $this->getDemoRouteDungeons();
        $this->getDemoRouteMapping();
        $this->getLatestRelease();
        $this->getLatestReleaseSpotlight();
        $this->getAppVersionInfo();
        $this->getUserCount();
        $this->getAllRegions();
        $this->getAllFactions();
        $this->getReleaseChangelogCategories();
        $this->getCharacterClassSpecializations();
        $this->getCharacterClasses();
        $this->getCharacterRacesClasses();
        $this->getAllAffixes();
        $this->getAllRouteAttributes();
        $this->getAllPublishedStates();
        $this->getSelectableSpellsByCategory();
        $this->getAllGameVersions();
        $this->getActiveExpansions();
        $this->getAllExpansions();
        $this->getDungeonsByExpansionIdDesc();
        $this->getRaidsByExpansionIdDesc();
        $this->getActiveDungeonsByExpansionIdDesc();
        $this->getActiveRaidsByExpansionIdDesc();
        $this->getSiegeOfBoralus();
        $this->getAffixGroupEaseTiersByAffixGroup();
        $this->getDungeonExpansions();
        $this->getAllSpeedrunDungeons();

        $this->forceRefresh = false;
    }

    public function getCurrentExpansionForRegion(GameServerRegion $gameServerRegion): Expansion
    {
        return $this->cachedGlobal(
            sprintf('current_expansion:%s', $gameServerRegion->short),
            fn() => $this->expansionService->getCurrentExpansion($gameServerRegion),
            3600,
        );
    }

    public function getCurrentSeasonForRegion(GameServerRegion $gameServerRegion): ?Season
    {
        return $this->cachedGlobal(
            sprintf('current_season:%s', $gameServerRegion->short),
            fn() => $this->expansionService->getCurrentSeason($this->getCurrentExpansionForRegion($gameServerRegion), $gameServerRegion),
            3600,
        );
    }

    public function getNextSeasonForRegion(GameServerRegion $gameServerRegion): ?Season
    {
        return $this->cachedGlobal(
            sprintf('next_season:%s', $gameServerRegion->short),
            function () use ($gameServerRegion) {
                // Fall back to the current expansion if the next expansion is not known yet, then the next season
                // is still part of the current expansion
                $nextExpansion = $this->expansionService->getNextExpansion($gameServerRegion) ?? $this->getCurrentExpansionForRegion($gameServerRegion);

                return $this->expansionService->getNextSeason($nextExpansion, $gameServerRegion);
            },
            3600,
        );
    }

    /**
     * @return Collection<string, ExpansionData>
     */
    public function getExpansionsData(GameServerRegion $gameServerRegion): Collection
    {
        return $this->cachedGlobal(
            sprintf('expansions_data:%s', $gameServerRegion->short),
            function () use ($gameServerRegion) {
                $allExpansions = Expansion::with(['dungeonsAndRaids'])->orderBy('released_at', 'desc')->get();

                /** @var Collection<string, ExpansionData> $expansionsData */
                $expansionsData = collect();
                foreach ($allExpansions as $expansion) {
                    $expansionsData->put($expansion->shortname, $this->expansionService->getData($this->seasonAffixGroupService, $expansion, $gameServerRegion));
                }

                return $expansionsData;
            },
            3600,
        );
    }

    /**
     * All valid affix groups we may select across all seasons (used by the create-route affix selector).
     *
     * @return Collection<int, AffixGroup>
     */
    public function getAllAffixGroupsForRegion(GameServerRegion $gameServerRegion): Collection
    {
        return $this->cachedGlobal(
            sprintf('all_affix_groups:%s', $gameServerRegion->short),
            function () use ($gameServerRegion) {
                $allAffixGroups = collect();
                foreach ($this->getExpansionsData($gameServerRegion) as $expansionData) {
                    $allAffixGroups = $allAffixGroups->merge($expansionData->getExpansionSeason()->getAffixGroups()->getAllAffixGroups());
                }

                return $allAffixGroups;
            },
            3600,
        );
    }

    /**
     * The current affix group per expansion shortname (used by the create-route affix selector).
     *
     * @return Collection<string, AffixGroup|null>
     */
    public function getAllCurrentAffixesForRegion(GameServerRegion $gameServerRegion): Collection
    {
        return $this->cachedGlobal(
            sprintf('all_current_affixes:%s', $gameServerRegion->short),
            function () use ($gameServerRegion) {
                $allCurrentAffixes = collect();
                foreach ($this->getExpansionsData($gameServerRegion) as $expansionData) {
                    $allCurrentAffixes->put($expansionData->getExpansion()->shortname, $expansionData->getExpansionSeason()->getAffixGroups()->getCurrentAffixGroup());
                }

                return $allCurrentAffixes;
            },
            3600,
        );
    }

    /**
     * All affix groups grouped by active expansion shortname (used by the discover/heatmap search filters).
     *
     * @return Collection<string, Collection<int, AffixGroup>>
     */
    public function getAllAffixGroupsByActiveExpansion(GameServerRegion $gameServerRegion): Collection
    {
        return $this->cachedGlobal(
            sprintf('all_affix_groups_by_active_expansion:%s', $gameServerRegion->short),
            function () use ($gameServerRegion) {
                $expansionsData                  = $this->getExpansionsData($gameServerRegion);
                $allAffixGroupsByActiveExpansion = collect();
                foreach ($this->getActiveExpansions() as $activeExpansion) {
                    /** @var ExpansionData $expansionData */
                    $expansionData = $expansionsData->get($activeExpansion->shortname);
                    $allAffixGroupsByActiveExpansion->put($expansionData->getExpansion()->shortname, $expansionData->getExpansionSeason()->getAffixGroups()->getAllAffixGroups());
                }

                return $allAffixGroupsByActiveExpansion;
            },
            3600,
        );
    }

    /**
     * The featured affixes grouped by active expansion shortname (used by the discover/heatmap search filters).
     *
     * @return Collection<string, Collection<int, Affix>>
     */
    public function getFeaturedAffixesByActiveExpansion(GameServerRegion $gameServerRegion): Collection
    {
        return $this->cachedGlobal(
            sprintf('featured_affixes_by_active_expansion:%s', $gameServerRegion->short),
            function () use ($gameServerRegion) {
                $expansionsData                   = $this->getExpansionsData($gameServerRegion);
                $featuredAffixesByActiveExpansion = collect();
                foreach ($this->getActiveExpansions() as $activeExpansion) {
                    /** @var ExpansionData $expansionData */
                    $expansionData = $expansionsData->get($activeExpansion->shortname);
                    $featuredAffixesByActiveExpansion->put($expansionData->getExpansion()->shortname, $expansionData->getExpansionSeason()->getAffixGroups()->getFeaturedAffixes());
                }

                return $featuredAffixesByActiveExpansion;
            },
            3600,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getGameServerRegionViewVariables(GameServerRegion $gameServerRegion, bool $useCache = true): array
    {
        $previousForceRefresh = $this->forceRefresh;
        $this->forceRefresh   = $this->forceRefresh || !$useCache;

        try {
            return [
                // Expansions/season data
                'expansionsData' => $this->getExpansionsData($gameServerRegion),

                'currentSeason'    => $this->getCurrentSeasonForRegion($gameServerRegion),
                'nextSeason'       => $this->getNextSeasonForRegion($gameServerRegion),
                'currentExpansion' => $this->getCurrentExpansionForRegion($gameServerRegion),

                // Search
                'allAffixGroupsByActiveExpansion'  => $this->getAllAffixGroupsByActiveExpansion($gameServerRegion),
                'featuredAffixesByActiveExpansion' => $this->getFeaturedAffixesByActiveExpansion($gameServerRegion),

                // Create route
                'allAffixGroups'    => $this->getAllAffixGroupsForRegion($gameServerRegion),
                'allCurrentAffixes' => $this->getAllCurrentAffixesForRegion($gameServerRegion),
            ];
        } finally {
            $this->forceRefresh = $previousForceRefresh;
        }
    }

    public function shouldLoadViewVariables(string $pathInfo): bool
    {
        $isWhitelisted = collect(self::VIEW_VARIABLES_URL_WHITELIST)->contains(static fn($url) => Str::startsWith($pathInfo, $url));

        if (!$isWhitelisted) {
            // If it's blacklisted..
            if (collect(self::VIEW_VARIABLES_URL_BLACKLIST)->contains(static fn($url) => Str::startsWith($pathInfo, $url))) {
                // Don't set the view variables at all
                return false;
            }
        }

        return true;
    }

    /**
     * Base query shared by the dungeon/raid getters, ordered by most recent expansion then dungeon name.
     *
     * @return Builder<Dungeon>
     */
    private function dungeonsByExpansionQuery(): Builder
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Dungeon::select('dungeons.*')
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->orderByRaw('expansions.released_at DESC, dungeons.name');
    }

    /**
     * Memoizes (per request) and caches (per release) the result of $compute under its own cache key.
     *
     * Region/season-sensitive data should pass a shorter $localTtl (e.g. 3600) since the current/next
     * expansion and season can change every hour.
     */
    private function cachedGlobal(string $name, Closure $compute, int $localTtl = 86400): mixed
    {
        $key = sprintf('view_data:%s:%s', $this->release, $name);

        return once(fn() => $this->rememberLocal(
            $key,
            $localTtl,
            fn() => $this->cacheService->setCacheEnabled(!$this->forceRefresh)
                ->remember($key, $compute, config('keystoneguru.cache.global_view_variables.ttl')),
        ));
    }
}
