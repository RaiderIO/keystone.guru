<?php

namespace App\Service\View;

use App\Models\Affix;
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
use App\Models\Spell\Spell;
use App\Models\User;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionData;
use App\Service\Expansion\ExpansionServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Str;

class ViewService implements ViewServiceInterface
{
    private const VIEW_VARIABLES_URL_WHITELIST = [
        // search actually renders views back to the user which we need
        '/ajax/search',
    ];

    private const VIEW_VARIABLES_URL_BLACKLIST = [
        '/ajax/',
        '/api/',
        '/benchmark',
    ];

    private string $release;

    public function __construct(
        private readonly CacheServiceInterface              $cacheService,
        private readonly ExpansionServiceInterface          $expansionService,
        private readonly AffixGroupEaseTierServiceInterface $easeTierService,
    ) {
        // Load the release version from the file, this is used to cache view variables
        // We want to cache view variables per release so we don't mix up view variables between releases
        $this->release = file_get_contents(base_path('version')) ?: 'unknown';
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalViewVariables(bool $useCache = true): array
    {
        return $this->cacheService->setCacheEnabled($useCache)->remember(sprintf('view_variables:%s:global', $this->release), function () {
            // Build a list of some common
            $demoRoutes = DungeonRoute::where('demo', true)
                // @TODO Temp fix for testing environment
                ->without(['thumbnails'])
                ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD_WITH_LINK])
                ->orderBy('dungeon_id')
                ->get();

            $demoRouteDungeons = Dungeon::whereIn('id', $demoRoutes->pluck(['dungeon_id']))->get();

            $dungeonsSelectQuery = Dungeon::select('dungeons.*')
                ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
                ->orderByRaw('expansions.released_at DESC, dungeons.name');
            $raidsSelectQuery    = $dungeonsSelectQuery->clone()
                ->where('dungeons.raid', true);

            $allDungeonsByExpansionId = $dungeonsSelectQuery
                ->where('dungeons.raid', false)
                ->get();

            $allRaidsByExpansionId = $raidsSelectQuery
                ->get();

            $activeDungeonsByExpansionId = $dungeonsSelectQuery
                ->where('expansions.active', true)
                ->where('dungeons.active', true)
                ->get();

            $activeRaidsByExpansionId = $raidsSelectQuery
                ->where('expansions.active', true)
                ->where('dungeons.active', true)
                ->get();

            /** @var Release $latestRelease */
            $latestReleaseBuilder = Release::when(config('app.env') === 'production',
                static fn($query) => $query->where('released', true)
            );

            $latestRelease          = $latestReleaseBuilder->latest()->first();
            $latestReleaseSpotlight = $latestReleaseBuilder->where('spotlight', true)
                ->whereDate('created_at', '>',
                    Carbon::now()->subDays(config('keystoneguru.releases.spotlight_show_days', 7))->toDateTimeString()
                )->first();

            $allRegions    = GameServerRegion::all();
            $allExpansions = Expansion::with(['dungeons', 'raids'])->orderBy('released_at', 'desc')->get();

            /** @var Collection<Expansion> $activeExpansions */
            $activeExpansions = Expansion::active()->with(['dungeons', 'raids'])->orderBy('released_at', 'desc')->get();

            // Spells
            $selectableSpellsByCategory = Spell::where('selectable', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->groupBy('category')
                ->mapWithKeys(static fn(Collection $spells, string $key) => [__($key) => $spells]);

            $appRevision = trim(file_get_contents(base_path('version')));

            return [
                'isLocal'                         => config('app.env') === 'local',
                'isMapping'                       => config('app.env') === 'mapping',
                'isProduction'                    => config('app.env') === 'production',
                'demoRoutes'                      => $demoRoutes,
                'demoRouteDungeons'               => $demoRouteDungeons,
                'demoRouteMapping'                => $demoRouteDungeons
                    ->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->id => $demoRoutes->where('dungeon_id', $dungeon->id)->first()->public_key]),
                'latestRelease'                   => $latestRelease,
                'latestReleaseSpotlight'          => $latestReleaseSpotlight,
                'appVersion'                      => $latestRelease->version,
                'appRevision'                     => $appRevision,
                'appVersionAndName'               => sprintf(
                    '%s® © 2018-%d %s - %s (%s)',
                    config('app.name'),
                    date('Y'),
                    'Ludicrous Speed, LLC.',
                    $latestRelease->version,
                    substr($appRevision, 0, 6)
                ),

                // Home
                'userCount'                       => (int)(User::count() / 1000) * 1000,

                // OAuth/register
                'allRegions'                      => $allRegions,

                // Composition
                'allFactions'                     => Faction::all(),

                // Changelog
                'releaseChangelogCategories'      => ReleaseChangelogCategory::all(),

                // Map
                'characterClassSpecializations'   => CharacterClassSpecialization::with('class')->get(),
                'characterClasses'                => CharacterClass::with('specializations')->orderBy('name')->get(),
                // @TODO Classes are loaded fully inside $raceClasses, this shouldn't happen. Find a way to exclude them
                'characterRacesClasses'           => CharacterRace::with(['classes:character_classes.id'])->orderBy('faction_id')->get(),
                'allAffixes'                      => Affix::all(),
                'allRouteAttributes'              => RouteAttribute::all(),
                'allPublishedStates'              => PublishedState::all(),
                'selectableSpellsByCategory'      => $selectableSpellsByCategory,

                // Misc
                'allGameVersions'                 => GameVersion::active()->get(),
                'activeExpansions'                => $activeExpansions, // Show most recent expansions first
                'allExpansions'                   => $allExpansions,
                'dungeonsByExpansionIdDesc'       => $allDungeonsByExpansionId,
                'raidsByExpansionIdDesc'          => $allRaidsByExpansionId,
                // Take active expansions into account
                'activeDungeonsByExpansionIdDesc' => $activeDungeonsByExpansionId,
                'activeRaidsByExpansionIdDesc'    => $activeRaidsByExpansionId,
                'siegeOfBoralus'                  => Dungeon::where('key', Dungeon::DUNGEON_SIEGE_OF_BORALUS)->first(),

                // Discover
                'affixGroupEaseTiersByAffixGroup' => $this->easeTierService->getTiers()->groupBy(['affix_group_id', 'dungeon_id']),

                // Create route
                'dungeonExpansions'               => $allDungeonsByExpansionId
                    ->pluck('expansion_id', 'id')->mapWithKeys(static fn(int $expansionId, int $dungeonId) => [$dungeonId => $allExpansions->where('id', $expansionId)->first()->shortname]),
                'allSpeedrunDungeons'             => Dungeon::where('speedrun_enabled', true)->get(),
            ];
        }, config('keystoneguru.cache.global_view_variables.ttl'));
    }

    public function getGameServerRegionViewVariables(GameServerRegion $gameServerRegion, bool $useCache = true): array
    {
        return $this->cacheService->setCacheEnabled($useCache)->remember(
            sprintf('view_variables:%s:game_server_region:%s', $this->release, $gameServerRegion->short),
            function () use ($gameServerRegion) {
                // So we're already caching the result of this function, Model Cache doesn't need to be involved at this time
                // The results will likely explode the model cache (and redis usage as a result) so don't use it
                $currentExpansion = $this->expansionService->getCurrentExpansion($gameServerRegion);
                $currentSeason    = $this->expansionService->getCurrentSeason($currentExpansion, $gameServerRegion);

                // Fall back to the current expansion if the next expansion is not known yet, then the next season
                // is still part of the current expansion
                $nextExpansion = $this->expansionService->getNextExpansion($gameServerRegion) ?? $currentExpansion;
                $nextSeason    = $this->expansionService->getNextSeason($nextExpansion, $gameServerRegion);

                $allExpansions = Expansion::with(['dungeonsAndRaids'])->orderBy('released_at', 'desc')->get();

                /** @var Collection<ExpansionData> $expansionsData */
                $expansionsData = collect();
                foreach ($allExpansions as $expansion) {
                    $expansionsData->put($expansion->shortname, $this->expansionService->getData($expansion, $gameServerRegion));
                }

                /** @var Collection<Expansion> $activeExpansions */
                $activeExpansions = Expansion::active()->with('dungeonsAndRaids')->orderBy('released_at', 'desc')->get();

                // Build a list of all valid affix groups we may select across all currently active seasons
                $allAffixGroups    = collect();
                $allCurrentAffixes = collect();
                foreach ($expansionsData as $expansionData) {
                    $allAffixGroups = $allAffixGroups->merge($expansionData->getExpansionSeason()->getAffixGroups()->getAllAffixGroups());
                    $allCurrentAffixes->put($expansionData->getExpansion()->shortname, $expansionData->getExpansionSeason()->getAffixGroups()->getCurrentAffixGroup());
                }

                // Gather all affix groups by active expansions
                $allAffixGroupsByActiveExpansion  = collect();
                $featuredAffixesByActiveExpansion = collect();
                foreach ($activeExpansions as $activeExpansion) {
                    /** @var ExpansionData $expansionData */
                    $expansionData = $expansionsData->get($activeExpansion->shortname);
                    $allAffixGroupsByActiveExpansion->put($expansionData->getExpansion()->shortname, $expansionData->getExpansionSeason()->getAffixGroups()->getAllAffixGroups());
                    $featuredAffixesByActiveExpansion->put($expansionData->getExpansion()->shortname, $expansionData->getExpansionSeason()->getAffixGroups()->getFeaturedAffixes());
                }

                return [
                    // Expansions/season data
                    'expansionsData' => $expansionsData,

                    'currentSeason'                    => $currentSeason,
                    'nextSeason'                       => $nextSeason,
                    'currentExpansion'                 => $currentExpansion,

                    // Search
                    'allAffixGroupsByActiveExpansion'  => $allAffixGroupsByActiveExpansion,
                    'featuredAffixesByActiveExpansion' => $featuredAffixesByActiveExpansion,

                    // Create route
                    'allAffixGroups'                   => $allAffixGroups,
                    'allCurrentAffixes'                => $allCurrentAffixes,
                ];
            }, config('keystoneguru.cache.global_view_variables.ttl'));
    }

    public function shouldLoadViewVariables(string $uri): bool
    {
        $isWhitelisted = collect(self::VIEW_VARIABLES_URL_WHITELIST)->contains(static function ($url) use ($uri) {
            return Str::startsWith($uri, $url);
        });

        if (!$isWhitelisted) {
            // If it's blacklisted..
            if (collect(self::VIEW_VARIABLES_URL_BLACKLIST)->contains(static function ($url) use ($uri) {
                return Str::startsWith($uri, $url);
            })) {
                // Don't set the view variables at all
                return false;
            }
        }

        return true;
    }
}
