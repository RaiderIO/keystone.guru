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
use App\Models\Spell;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionData;
use App\Service\Expansion\ExpansionServiceInterface;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class ViewService implements ViewServiceInterface
{
    /** @var CacheServiceInterface */
    private $cacheService;

    /** @var ExpansionServiceInterface */
    private $expansionService;

    /** @var AffixGroupEaseTierServiceInterface */
    private $easeTierService;

    public function __construct()
    {
        $this->cacheService     = App::make(CacheServiceInterface::class);
        $this->expansionService = App::make(ExpansionServiceInterface::class);
        $this->easeTierService  = App::make(AffixGroupEaseTierServiceInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function getGlobalViewVariables(bool $useCache = true): array
    {
        return $this->cacheService->setCacheEnabled($useCache)->remember('global_view_variables', function () {
            // Build a list of some common
            $demoRoutes = DungeonRoute::where('demo', true)
                ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD_WITH_LINK])
                ->orderBy('dungeon_id')->get();

            $demoRouteDungeons = Dungeon::whereIn('id', $demoRoutes->pluck(['dungeon_id']))->get();

            $dungeonsSelectQuery = Dungeon::select('dungeons.*')
                ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
                ->orderByRaw('expansions.released_at DESC, dungeons.name');

            $allDungeonsByExpansionId = $dungeonsSelectQuery
                ->get();

            $activeDungeonsByExpansionId = $dungeonsSelectQuery
                ->where('expansions.active', true)
                ->where('dungeons.active', true)
                ->get();

            /** @var Release $latestRelease */
            $latestRelease          = Release::latest()->first();
            $latestReleaseSpotlight = Release::where('spotlight', true)
                ->whereDate('created_at', '>',
                    Carbon::now()->subDays(config('keystoneguru.releases.spotlight_show_days', 7))
                )->latest()->first();

            $allRegions    = GameServerRegion::all();
            $allExpansions = Expansion::with(['dungeons'])->orderBy('released_at', 'desc')->get();

            /** @var Collection|Expansion[] $activeExpansions */
            $activeExpansions = Expansion::active()->with('dungeons')->orderBy('released_at', 'desc')->get();

            // Spells
            $selectableSpellsByCategory = Spell::where('selectable', true)
                ->get()
                ->groupBy('category')
                ->mapWithKeys(fn(Collection $spells, string $key) => [__($key) => $spells]);

            $appRevision = trim(file_get_contents(base_path('version')));

            return [
                'isProduction'                    => config('app.env') === 'production',
                'demoRoutes'                      => $demoRoutes,
                'demoRouteDungeons'               => $demoRouteDungeons,
                'demoRouteMapping'                => $demoRouteDungeons
                    ->mapWithKeys(fn(Dungeon $dungeon) => [$dungeon->id => $demoRoutes->where('dungeon_id', $dungeon->id)->first()->public_key]),
                'latestRelease'                   => $latestRelease,
                'latestReleaseSpotlight'          => $latestReleaseSpotlight,
                'appVersion'                      => $latestRelease->version,
                'appRevision'                     => $appRevision,
                'appVersionAndName'               => sprintf(
                    '%s/%s-%s',
                    config('app.name'),
                    $latestRelease->version,
                    substr($appRevision, 0, 6)
                ),

                // Home
                'userCount'                       => User::count(),

                // OAuth/register
                'allRegions'                      => $allRegions,

                // Composition
                'allFactions'                     => Faction::all(),

                // Changelog
                'releaseChangelogCategories'      => ReleaseChangelogCategory::all(),

                // Map
                'characterClassSpecializations'   => CharacterClassSpecialization::all(),
                'characterClasses'                => CharacterClass::with('specializations')->get(),
                // @TODO Classes are loaded fully inside $raceClasses, this shouldn't happen. Find a way to exclude them
                'characterRacesClasses'           => CharacterRace::with(['classes:character_classes.id'])->get(),
                'allAffixes'                      => Affix::all(),
                'allRouteAttributes'              => RouteAttribute::all(),
                'allPublishedStates'              => PublishedState::all(),
                'selectableSpellsByCategory'      => $selectableSpellsByCategory,

                // Misc
                'allGameVersions'                 => GameVersion::all(),
                'activeExpansions'                => $activeExpansions, // Show most recent expansions first
                'allExpansions'                   => $allExpansions,
                'dungeonsByExpansionIdDesc'       => $allDungeonsByExpansionId,
                // Take active expansions into account
                'activeDungeonsByExpansionIdDesc' => $activeDungeonsByExpansionId,
                'siegeOfBoralus'                  => Dungeon::where('key', Dungeon::DUNGEON_SIEGE_OF_BORALUS)->first(),

                // Discover
                'affixGroupEaseTiersByAffixGroup' => $this->easeTierService->getTiers()->groupBy(['affix_group_id', 'dungeon_id']),

                // Create route
                'dungeonExpansions'               => $allDungeonsByExpansionId
                    ->pluck('expansion_id', 'id')->mapWithKeys(fn(int $expansionId, int $dungeonId) => [$dungeonId => $allExpansions->where('id', $expansionId)->first()->shortname]),
                'allSpeedrunDungeons'             => Dungeon::where('speedrun_enabled', true)->get(),
            ];
        }, config('keystoneguru.cache.global_view_variables.ttl'));
    }

    /**
     * @param GameServerRegion $gameServerRegion
     * @param bool             $useCache
     * @return array
     */
    public function getGameServerRegionViewVariables(GameServerRegion $gameServerRegion, bool $useCache = true): array
    {
        return $this->cacheService->setCacheEnabled($useCache)->remember(
            sprintf('game_server_region_%s_view_variables', $gameServerRegion->short),
            function () use ($gameServerRegion) {
                $currentExpansion = $this->expansionService->getCurrentExpansion($gameServerRegion);
                $currentSeason    = $this->expansionService->getCurrentSeason($currentExpansion, $gameServerRegion);

                // Fall back to the current expansion if the next expansion is not known yet, then the next season
                // is still part of the current expansion
                $nextExpansion = $this->expansionService->getNextExpansion($gameServerRegion) ?? $currentExpansion;
                $nextSeason    = $this->expansionService->getNextSeason($nextExpansion, $gameServerRegion);

                $allExpansions = Expansion::with(['dungeons'])->orderBy('released_at', 'desc')->get();

                /** @var Collection|ExpansionData[] $expansionsData */
                $expansionsData = collect();
                foreach ($allExpansions as $expansion) {
                    $expansionsData->put($expansion->shortname, $this->expansionService->getData($expansion, $gameServerRegion));
                }

                /** @var Collection|Expansion[] $activeExpansions */
                $activeExpansions = Expansion::active()->with('dungeons')->orderBy('released_at', 'desc')->get();

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
            });
    }
}
