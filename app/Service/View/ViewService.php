<?php


namespace App\Service\View;

use App\Models\Affix;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\GameServerRegion;
use App\Models\PublishedState;
use App\Models\Release;
use App\Models\ReleaseChangelogCategory;
use App\Models\RouteAttribute;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionData;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Subcreation\AffixGroupEaseTierServiceInterface;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Tremby\LaravelGitVersion\GitVersionHelper;

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
    public function getCache(bool $useCache = true): array
    {
        return $this->cacheService->setCacheEnabled($useCache)->remember('global_view_variables', function () {
            // Build a list of some common
            $demoRoutes = DungeonRoute::where('demo', true)
                ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD_WITH_LINK])
                ->orderBy('dungeon_id')->get();

            $demoRouteDungeons = Dungeon::whereIn('id', $demoRoutes->pluck(['dungeon_id']))->get();

            $dungeonsSelectQuery = Dungeon::select('dungeons.*')
                ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
                ->where('expansions.active', true)
                ->orderByRaw('expansion_id DESC, dungeons.name');

            $allDungeonsByExpansionId = $dungeonsSelectQuery
                ->get();

            $activeDungeonsByExpansionId = $allDungeonsByExpansionId
                ->where('active', true);

            /** @var Release $latestRelease */
            $latestRelease          = Release::latest()->first();
            $latestReleaseSpotlight = Release::where('spotlight', true)
                ->whereDate('created_at', '>',
                    Carbon::now()->subDays(config('keystoneguru.releases.spotlight_show_days', 7))
                )->latest()->first();

            $allRegions    = GameServerRegion::all();
            $allExpansions = Expansion::orderBy('released_at', 'desc')->get();

            /** @var Collection|ExpansionData[] $expansionsData */
            $expansionsData = collect();
            foreach ($allExpansions as $expansion) {
                $expansionsData->put($expansion->shortname, $this->expansionService->getData($expansion));
            }

            /** @var Collection|Expansion[] $activeExpansions */
            $activeExpansions = Expansion::active()->orderBy('released_at', 'desc')->get();

            // Build a list of all valid affix groups we may select across all currently active seasons
            $allAffixGroups    = collect();
            $allCurrentAffixes = collect();
            foreach ($expansionsData as $expansionData) {
                $allAffixGroups = $allAffixGroups->merge($expansionData->getExpansionSeason()->getAffixGroups()->getAllAffixGroups());
                $allCurrentAffixes->put($expansionData->getExpansion()->shortname, $expansionData->getExpansionSeason()->getAffixGroups()->getCurrentAffixGroups());
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
                'isProduction'                     => config('app.env') === 'production',
                'demoRoutes'                       => $demoRoutes,
                'demoRouteDungeons'                => $demoRouteDungeons,
                'demoRouteMapping'                 => $demoRouteDungeons
                    ->mapWithKeys(function (Dungeon $dungeon) use ($demoRoutes) {
                        return [$dungeon->id => $demoRoutes->where('dungeon_id', $dungeon->id)->first()->public_key];
                    }),
                'latestRelease'                    => $latestRelease,
                'latestReleaseSpotlight'           => $latestReleaseSpotlight,
                'appVersion'                       => GitVersionHelper::getVersion(),
                'appVersionAndName'                => GitVersionHelper::getNameAndVersion(),

                // Home
                'userCount'                        => User::count(),

                // OAuth/register
                'allRegions'                       => $allRegions,

                // Composition
                'allFactions'                      => Faction::all(),

                // Expansions/season data
                'expansionsData'                   => $expansionsData,

                // Changelog
                'releaseChangelogCategories'       => ReleaseChangelogCategory::all(),

                // Map
                'characterClassSpecializations'    => CharacterClassSpecialization::all(),
                'characterClasses'                 => CharacterClass::with('specializations')->get(),
                // @TODO Classes are loaded fully inside $raceClasses, this shouldn't happen. Find a way to exclude them
                'characterRacesClasses'            => CharacterRace::with(['classes:character_classes.id'])->get(),
                'affixes'                          => Affix::all(),
                'allRouteAttributes'               => RouteAttribute::all(),
                'allPublishedStates'               => PublishedState::all(),

                // Misc
                'activeExpansions'                 => $activeExpansions, // Show most recent expansions first
                'allExpansions'                    => $allExpansions,
                'dungeonsByExpansionIdDesc'        => Dungeon::orderByRaw('expansion_id DESC, name')->get(),
                // Take active expansions into account
                'activeDungeonsByExpansionIdDesc'  => $activeDungeonsByExpansionId,
                'siegeOfBoralus'                   => Dungeon::siegeOfBoralus()->first(),

                // Search
                'allAffixGroupsByActiveExpansion'  => $allAffixGroupsByActiveExpansion,
                'featuredAffixesByActiveExpansion' => $featuredAffixesByActiveExpansion,
                'currentExpansion'                 => $this->expansionService->getCurrentExpansion(),

                // Discover
                'affixGroupEaseTiersByAffixGroup'  => $this->easeTierService->getTiers()->groupBy(['affix_group_id', 'dungeon_id']),

                // Create route
                'allAffixGroups'                   => $allAffixGroups,
                'allCurrentAffixes'                => $allCurrentAffixes,
                'dungeonExpansions'                => $allDungeonsByExpansionId
                    ->pluck('expansion_id', 'id')->mapWithKeys(function (int $expansionId, int $dungeonId) use ($allExpansions) {
                        return [$dungeonId => $allExpansions->where('id', $expansionId)->first()->shortname];
                    }),
            ];
        }, config('keystoneguru.cache.global_view_variables.ttl'));
    }
}
