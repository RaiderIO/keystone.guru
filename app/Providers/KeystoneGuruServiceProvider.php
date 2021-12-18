<?php

namespace App\Providers;

use App\Models\Affix;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\GameServerRegion;
use App\Models\PaidTier;
use App\Models\PublishedState;
use App\Models\Release;
use App\Models\ReleaseChangelogCategory;
use App\Models\RouteAttribute;
use App\Models\UserReport;
use App\Service\Cache\CacheService;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Expansion\ExpansionData;
use App\Service\Expansion\ExpansionServiceInterface;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;
use Psr\SimpleCache\InvalidArgumentException;
use Tremby\LaravelGitVersion\GitVersionHelper;

class KeystoneGuruServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Bind the interface to the actual service
        $this->app->bind('App\Service\EchoServerHttpApiServiceInterface', 'App\Service\EchoServerHttpApiService');

        // Internals
        $this->app->bind('App\Service\Cache\CacheServiceInterface', 'App\Service\Cache\CacheService');

        // Model helpers
        if (config('app.env') === 'local') {
            $this->app->bind('App\Service\DungeonRoute\DiscoverServiceInterface', 'App\Service\DungeonRoute\DevDiscoverService');
        } else {
            $this->app->bind('App\Service\DungeonRoute\DiscoverServiceInterface', 'App\Service\DungeonRoute\DiscoverService');
        }
        $this->app->bind('App\Service\Expansion\ExpansionServiceInterface', 'App\Service\Expansion\ExpansionService');
        // Depends on ExpansionService
        $this->app->bind('App\Service\Season\SeasonServiceInterface', 'App\Service\Season\SeasonService');
        $this->app->bind('App\Service\LiveSession\OverpulledEnemyServiceInterface', 'App\Service\LiveSession\OverpulledEnemyService');
        $this->app->bind('App\Service\Mapping\MappingServiceInterface', 'App\Service\Mapping\MappingService');
        $this->app->bind('App\Service\Subcreation\AffixGroupEaseTierServiceInterface', 'App\Service\Subcreation\AffixGroupEaseTierService');
        // Depends on SeasonService
        $this->app->bind('App\Service\TimewalkingEvent\TimewalkingEventServiceInterface', 'App\Service\TimewalkingEvent\TimewalkingEventService');

        // External communication
        $this->app->bind('App\Service\Discord\DiscordApiServiceInterface', 'App\Service\Discord\DiscordApiService');
        $this->app->bind('App\Service\Reddit\RedditApiServiceInterface', 'App\Service\Reddit\RedditApiService');
        $this->app->bind('App\Service\Subcreation\SubcreationApiServiceInterface', 'App\Service\Subcreation\SubcreationApiService');
    }

    /**
     * Bootstrap services.
     *
     * @param CacheService $cacheService
     * @param ExpansionServiceInterface $expansionService
     * @param DiscoverServiceInterface $discoverService
     * @return void
     * @throws InvalidArgumentException
     */
    public function boot(CacheService $cacheService, ExpansionServiceInterface $expansionService, DiscoverServiceInterface $discoverService)
    {
        // There really is nothing here that's useful for console apps - migrations may fail trying to do the below anyways
        if (app()->runningInConsole()) {
            return;
        }

        // https://laravel.com/docs/8.x/upgrade#pagination
        Paginator::useBootstrap();

        // Cache some variables so we don't continuously query data that never changes (unless there's a patch)
        $globalViewVariables = $cacheService->remember('global_view_variables', function () use ($expansionService, $discoverService) {
            // Build a list of some common
            $demoRoutes = DungeonRoute::where('demo', true)
                ->where('published_state_id', PublishedState::where('name', PublishedState::WORLD_WITH_LINK)->first()->id)
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
            $allExpansions = Expansion::all();

            /** @var Collection|ExpansionData[] $expansionsData */
            $expansionsData = collect();
            foreach ($allExpansions as $expansion) {
                $expansionsData->put($expansion->shortname, $expansionService->getData($expansion));
            }

            // Build a list of all valid affix groups we may select across all currently active seasons
            $allAffixGroups    = collect();
            $allCurrentAffixes = collect();
            foreach ($expansionsData as $expansionData) {
                $allAffixGroups = $allAffixGroups->merge($expansionData->getExpansionSeason()->getAffixGroups()->getAllAffixGroups());
                $allCurrentAffixes->put($expansionData->getExpansion()->shortname, $expansionData->getExpansionSeason()->getAffixGroups()->getCurrentAffixGroups());
            }

            return [
                'isProduction'                    => config('app.env') === 'production',
                'demoRoutes'                      => $demoRoutes,
                'demoRouteDungeons'               => $demoRouteDungeons,
                'demoRouteMapping'                => $demoRouteDungeons
                    ->mapWithKeys(function (Dungeon $dungeon) use ($demoRoutes) {
                        return [$dungeon->id => $demoRoutes->where('dungeon_id', $dungeon->id)->first()->public_key];
                    }),
                'latestRelease'                   => $latestRelease,
                'latestReleaseSpotlight'          => $latestReleaseSpotlight,
                'appVersion'                      => GitVersionHelper::getVersion(),
                'appVersionAndName'               => GitVersionHelper::getNameAndVersion(),

                // Home
                'userCount'                       => User::count(),

                // OAuth/register
                'allRegions'                      => $allRegions,

                // Composition
                'allFactions'                     => Faction::all(),

                // Expansions/season data
                'expansionsData'                  => $expansionsData,

                // Changelog
                'releaseChangelogCategories'      => ReleaseChangelogCategory::all(),

                // Map
                'characterClassSpecializations'   => CharacterClassSpecialization::all(),
                'characterClasses'                => CharacterClass::with('specializations')->get(),
                // @TODO Classes are loaded fully inside $raceClasses, this shouldn't happen. Find a way to exclude them
                'characterRacesClasses'           => CharacterRace::with(['classes:character_classes.id'])->get(),
                'affixes'                         => Affix::all(),
                'allRouteAttributes'              => RouteAttribute::all(),
                'allPublishedStates'              => PublishedState::all(),

                // Misc
                'activeExpansions'                => Expansion::active()->orderBy('id', 'desc')->get(), // Show most recent expansions first
                'allExpansions'                   => $allExpansions,
                'dungeonsByExpansionIdDesc'       => Dungeon::orderByRaw('expansion_id DESC, name')->get(),
                // Take active expansions into account
                'activeDungeonsByExpansionIdDesc' => $activeDungeonsByExpansionId,
                'siegeOfBoralus'                  => Dungeon::siegeOfBoralus()->first(),

                // Create route
                'allAffixGroups'                  => $allAffixGroups,
                'allCurrentAffixes'               => $allCurrentAffixes,
                'dungeonExpansions'               => $allDungeonsByExpansionId
                    ->pluck('expansion_id', 'id')->mapWithKeys(function (int $expansionId, int $dungeonId) use ($allExpansions) {
                        return [$dungeonId => $allExpansions->where('id', $expansionId)->first()->shortname];
                    }),
            ];
        }, config('keystoneguru.cache.global_view_variables.ttl'));

        $userOrDefaultRegion = GameServerRegion::getUserOrDefaultRegion();

        // All views
        view()->share('isMobile', (new Agent())->isMobile());
        view()->share('isProduction', $globalViewVariables['isProduction']);
        view()->share('demoRoutes', $globalViewVariables['demoRoutes']);

        // Can use the Auth() global here!
        view()->composer('*', function (View $view) {
            // Don't include the viewName in the layouts - they must inherit from whatever calls it!
            if (strpos($view->getName(), 'layouts') !== 0) {
                $view->with('viewName', $view->getName());
            }

            $view->with('theme', $_COOKIE['theme'] ?? 'darkly');
            $view->with('isUserAdmin', Auth::check() && Auth::getUser()->hasRole('admin'));

            // Set a variable that checks if the user is adfree or not
            $view->with('adFree', config('app.env') !== 'local' && Auth::check() && Auth::user()->hasPaidTier(PaidTier::AD_FREE));
        });

        view()->composer(['dungeonroute.discover.discover', 'dungeonroute.discover.dungeon.overview'], function (View $view) {
        });

        // Home page
        view()->composer('home', function (View $view) use ($globalViewVariables) {
            $view->with('userCount', $globalViewVariables['userCount']);
            $view->with('demoRouteDungeons', $globalViewVariables['demoRouteDungeons']);
            $view->with('demoRouteMapping', $globalViewVariables['demoRouteMapping']);
        });

        // Main view
        view()->composer(['layouts.app', 'layouts.sitepage', 'layouts.map', 'admin.dashboard.layouts.app'], function (View $view) use ($globalViewVariables) {
            $view->with('version', $globalViewVariables['appVersion']);
            $view->with('nameAndVersion', $globalViewVariables['appVersionAndName']);
            $view->with('latestRelease', $globalViewVariables['latestRelease']);
            $view->with('latestReleaseSpotlight', $globalViewVariables['latestReleaseSpotlight']);
        });

        view()->composer(['layouts.app', 'common.layout.footer'], function (View $view) use ($globalViewVariables) {
            $view->with('hasNewChangelog', isset($_COOKIE['changelog_release']) ? $globalViewVariables['latestRelease']->id > (int)$_COOKIE['changelog_release'] : false);
        });

        view()->composer('common.layout.navuser', function (View $view) {
            $view->with('numUserReports', Auth::check() && Auth::user()->is_admin ? UserReport::where('status', 0)->count() : 0);
        });

        view()->composer('common.layout.header', function (View $view) use ($globalViewVariables) {
            $view->with('activeExpansions', $globalViewVariables['activeExpansions']);
        });

        view()->composer([
            'dungeonroute.discover.category',
            'dungeonroute.discover.dungeon.category',
            'misc.affixes',
            'dungeonroute.discover.discover',
            'dungeonroute.discover.dungeon.overview',
        ], function (View $view) use ($globalViewVariables, $expansionService, $userOrDefaultRegion) {
            /** @var Expansion $expansion */
            $expansion = $view->getData()['expansion'];

            /** @var ExpansionData $expansionsData */
            $expansionsData = $globalViewVariables['expansionsData']->get($expansion->shortname);

            $view->with('currentAffixGroup', $expansionsData->getExpansionSeason()->getAffixGroups()->getCurrentAffixGroup($userOrDefaultRegion));
            $view->with('nextAffixGroup', $expansionsData->getExpansionSeason()->getAffixGroups()->getNextAffixGroup($userOrDefaultRegion));
        });

        // Dungeon grid view
        view()->composer('dungeonroute.discover.search', function (View $view) use ($globalViewVariables) {
            $view->with('activeExpansions', $globalViewVariables['activeExpansions']);
        });

        view()->composer(['common.forms.oauth', 'common.forms.register'], function (View $view) use ($globalViewVariables) {
            $view->with('allRegions', $globalViewVariables['allRegions']);
        });

        // Displaying a release
        view()->composer('common.release.release', function (View $view) use ($globalViewVariables) {
            $view->with('categories', $globalViewVariables['releaseChangelogCategories']);
        });

        // Displaying affixes
        view()->composer('common.group.affixes', function (View $view) use ($globalViewVariables, $userOrDefaultRegion) {
            // Convert the current affixes list for ALL regions, and just take the ones that the current user's region has
            $currentAffixes = [];
            foreach ($globalViewVariables['allCurrentAffixes'] as $expansionShortname => $currentAffixGroupByRegion) {
                $currentAffixes[$expansionShortname] = $currentAffixGroupByRegion[$userOrDefaultRegion->short]->id;
            }

            $view->with('currentAffixes', $currentAffixes);
            $view->with('dungeonExpansions', $globalViewVariables['dungeonExpansions']);
            $view->with('allAffixGroups', $globalViewVariables['allAffixGroups']);
            $view->with('affixes', $globalViewVariables['affixes']);
            $view->with('expansionsData', $globalViewVariables['expansionsData']);
        });

        // Displaying a release
        view()->composer('common.group.composition', function (View $view) use ($globalViewVariables) {
            $view->with('specializations', $globalViewVariables['characterClassSpecializations']);
            $view->with('classes', $globalViewVariables['characterClasses']);
            $view->with('racesClasses', $globalViewVariables['characterRacesClasses']);
            $view->with('allFactions', $globalViewVariables['allFactions']);
        });

        // Dungeon selector
        view()->composer('common.dungeon.select', function (View $view) use ($globalViewVariables) {
            $view->with('allExpansions', $globalViewVariables['allExpansions']);
            $view->with('allDungeons', $globalViewVariables['dungeonsByExpansionIdDesc']);
            $view->with('allActiveDungeons', $globalViewVariables['activeDungeonsByExpansionIdDesc']);
            $view->with('siegeOfBoralus', $globalViewVariables['siegeOfBoralus']);
        });

        // Dungeonroute attributes selector, Dungeonroute table
        view()->composer(['common.dungeonroute.attributes', 'common.dungeonroute.table'], function (View $view) use ($globalViewVariables) {
            $view->with('allRouteAttributes', $globalViewVariables['allRouteAttributes']);
        });

        view()->composer('common.dungeonroute.publish', function (View $view) use ($globalViewVariables) {
            $view->with('allPublishedStates', $globalViewVariables['allPublishedStates']);
        });

        // Team selector
        view()->composer('common.team.select', function (View $view) use ($globalViewVariables) {
            $view->with('teams', Auth::check() ? Auth::user()->teams : []);
        });

        // Profile pages
        view()->composer('profile.edit', function (View $view) use ($globalViewVariables) {
            $view->with('allClasses', $globalViewVariables['characterClasses']);
            $view->with('allRegions', $globalViewVariables['allRegions']);
        });
    }
}
