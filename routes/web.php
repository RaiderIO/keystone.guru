<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Features\ApiTokens;
use App\Features\CreatorProfiles;
use App\Features\Notifications;
use App\Features\WalkGraphLayer;
use App\Http\Controllers\Admin\AdminDungeonRouteController;
use App\Http\Controllers\AdminTools\AdminToolsArtisanCommandsController;
use App\Http\Controllers\AdminTools\AdminToolsAutoRouteCoverageController;
use App\Http\Controllers\AdminTools\AdminToolsBannedIpAddressController;
use App\Http\Controllers\AdminTools\AdminToolsCombatLogController;
use App\Http\Controllers\AdminTools\AdminToolsCombatLogCriteriaController;
use App\Http\Controllers\AdminTools\AdminToolsCombatLogRunDataController;
use App\Http\Controllers\AdminTools\AdminToolsDataDumpController;
use App\Http\Controllers\AdminTools\AdminToolsDungeonRouteController;
use App\Http\Controllers\AdminTools\AdminToolsEnemyForcesController;
use App\Http\Controllers\AdminTools\AdminToolsExceptionController;
use App\Http\Controllers\AdminTools\AdminToolsFeaturesController;
use App\Http\Controllers\AdminTools\AdminToolsGenerateTestRoutesController;
use App\Http\Controllers\AdminTools\AdminToolsMdtController;
use App\Http\Controllers\AdminTools\AdminToolsMessageBannerController;
use App\Http\Controllers\AdminTools\AdminToolsNotificationController;
use App\Http\Controllers\AdminTools\AdminToolsNpcController;
use App\Http\Controllers\AdminTools\AdminToolsPatreonGrantsController;
use App\Http\Controllers\AdminTools\AdminToolsSpellsController;
use App\Http\Controllers\AdminTools\AdminToolsTelemetryController;
use App\Http\Controllers\AdminTools\AdminToolsThumbnailsController;
use App\Http\Controllers\AdminTools\AdminToolsWagoGgController;
use App\Http\Controllers\AdminToolsController;
use App\Http\Controllers\AffixController;
use App\Http\Controllers\AffixGroupController;
use App\Http\Controllers\Ajax\AjaxAdminCombatLogRouteController;
use App\Http\Controllers\Ajax\AjaxArrowController;
use App\Http\Controllers\Ajax\AjaxBrushlineController;
use App\Http\Controllers\Ajax\AjaxDungeonFloorSwitchMarkerController;
use App\Http\Controllers\Ajax\AjaxDungeonRouteCollectionController;
use App\Http\Controllers\Ajax\AjaxDungeonRouteController;
use App\Http\Controllers\Ajax\AjaxDungeonRouteSearchController;
use App\Http\Controllers\Ajax\AjaxDungeonStartController;
use App\Http\Controllers\Ajax\AjaxDungeonTransportController;
use App\Http\Controllers\Ajax\AjaxEnemyController;
use App\Http\Controllers\Ajax\AjaxEnemyForcesCheckpointController;
use App\Http\Controllers\Ajax\AjaxEnemyPackController;
use App\Http\Controllers\Ajax\AjaxEnemyPatrolController;
use App\Http\Controllers\Ajax\AjaxHeatmapController;
use App\Http\Controllers\Ajax\AjaxKillZoneController;
use App\Http\Controllers\Ajax\AjaxLiveSessionController;
use App\Http\Controllers\Ajax\AjaxMapIconController;
use App\Http\Controllers\Ajax\AjaxMappingVersionController;
use App\Http\Controllers\Ajax\AjaxMetricController;
use App\Http\Controllers\Ajax\AjaxMountableAreaController;
use App\Http\Controllers\Ajax\AjaxNpcController;
use App\Http\Controllers\Ajax\AjaxOverpulledEnemyController;
use App\Http\Controllers\Ajax\AjaxPathController;
use App\Http\Controllers\Ajax\AjaxProfileController;
use App\Http\Controllers\Ajax\AjaxSiteController;
use App\Http\Controllers\Ajax\AjaxSpellController;
use App\Http\Controllers\Ajax\AjaxTagController;
use App\Http\Controllers\Ajax\AjaxTeamController;
use App\Http\Controllers\Ajax\AjaxUserController;
use App\Http\Controllers\Ajax\AjaxUserReportController;
use App\Http\Controllers\Ajax\AjaxViewController;
use App\Http\Controllers\Ajax\Floor\AjaxFloorUnionAreaController;
use App\Http\Controllers\Ajax\Floor\AjaxFloorUnionController;
use App\Http\Controllers\Ajax\WalkGraph\AjaxWalkGraphController;
use App\Http\Controllers\Auth\BattleNetLoginController;
use App\Http\Controllers\Auth\DiscordLoginController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Compendium\ClassCompendiumController;
use App\Http\Controllers\Compendium\CompendiumController;
use App\Http\Controllers\Compendium\NpcCompendiumController;
use App\Http\Controllers\Compendium\SpellCompendiumController;
use App\Http\Controllers\Compendium\SpellTuningCompendiumController;
use App\Http\Controllers\CreatorDirectoryController;
use App\Http\Controllers\Dungeon\DungeonController;
use App\Http\Controllers\Dungeon\DungeonExploreController;
use App\Http\Controllers\Dungeon\DungeonHeatmapController;
use App\Http\Controllers\Dungeon\MappingVersionController;
use App\Http\Controllers\DungeonRoute\DungeonRouteCollectionController;
use App\Http\Controllers\DungeonRoute\DungeonRouteController;
use App\Http\Controllers\DungeonRoute\DungeonRouteDiscoverController;
use App\Http\Controllers\DungeonRoute\DungeonRouteDiscoverExpansionSeasonController;
use App\Http\Controllers\DungeonRoute\DungeonRouteLegacyController;
use App\Http\Controllers\DungeonRoute\DungeonRouteSearchController;
use App\Http\Controllers\ExpansionController;
use App\Http\Controllers\Floor\FloorController;
use App\Http\Controllers\GameVersionController;
use App\Http\Controllers\Javascript\JavascriptController;
use App\Http\Controllers\LiveSessionController;
use App\Http\Controllers\LiveSessionLegacyController;
use App\Http\Controllers\MDTImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NpcController;
use App\Http\Controllers\NpcEnemyForcesController;
use App\Http\Controllers\NpcHealthController;
use App\Http\Controllers\PatreonController;
use App\Http\Controllers\ProfileApiTokenController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Speedrun\DungeonSpeedrunRequiredNpcsController;
use App\Http\Controllers\SpellController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserReportController;
use App\Http\Controllers\Webhook\GithubWebhookController;
use App\Http\Controllers\Webhook\WowheadWebhookController;
use App\Http\Middleware\WowheadCors;
use App\Models\DungeonDifficulty;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

// Webhooks
Route::prefix('webhook')->group(static function () {
    Route::post('github', [GithubWebhookController::class, 'github'])->name('webhook.github');
    Route::middleware([WowheadCors::class])->group(static function () {
        Route::options('wowhead/spell', [WowheadWebhookController::class, 'wowheadOptions']);
        Route::post('wowhead/spell', [WowheadWebhookController::class, 'wowheadSpell'])->name('webhook.wowhead.spell');

        Route::options('wowhead/npc', [WowheadWebhookController::class, 'wowheadOptions']);
        Route::post('wowhead/npc', [WowheadWebhookController::class, 'wowheadNpc'])->name('webhook.wowhead.npc');
    });
});

Route::middleware(['viewcachebuster', 'language', 'debugbarmessagelogger', 'read_only_mode', 'debug_info_context_logger', 'track_ip'])->group(static function () {
    Auth::routes();

    /** Local usage only */
    Route::get('js-gen/{dungeon}/data/{mappingVersion}/{mapFacadeStyle}.js', [JavascriptController::class, 'mapContextMappingVersionData'])->name('js.mapcontext.mapping_version_data');
    Route::get('js-gen/{dungeon}/data/{locale}.js', [JavascriptController::class, 'mapContextDungeonData'])->name('js.mapcontext.dungeon_data');
    Route::get('js-gen/static/{locale}.js', [JavascriptController::class, 'mapContextStaticData'])->name('js.mapcontext.static');

    // Catch for hard-coded /home route in RedirectsUsers.php
    Route::get('home', [SiteController::class, 'home']);
    Route::get('credits', [SiteController::class, 'credits'])->name('misc.credits');
    Route::get('about', [SiteController::class, 'about'])->name('misc.about');
    Route::get('privacy', [SiteController::class, 'privacy'])->name('legal.privacy');
    Route::get('terms', [SiteController::class, 'terms'])->name('legal.terms');
    Route::get('cookies', [SiteController::class, 'cookies'])->name('legal.cookies');
    Route::get('/', [SiteController::class, 'index'])->name('home');
    // Release notes now live on GitHub Releases; redirect old links there
    Route::get('changelog', [SiteController::class, 'changelog'])->name('misc.changelog');
    Route::get('release/{version}', [SiteController::class, 'release'])->name('release.view');
    Route::get('health', [SiteController::class, 'health'])->name('misc.health');
    Route::get('mapping', [SiteController::class, 'mapping'])->name('misc.mapping');
    Route::get('affixes', [SiteController::class, 'affixes'])->name('misc.affixes');
    Route::get('embed/explore/{gameVersion}/{dungeon}', [SiteController::class, 'embedExplore'])->name('misc.embed.explore');
    Route::get('embed/explore/{gameVersion}/{dungeon}/{floorIndex}', [SiteController::class, 'embedExplore'])->name('misc.embed.explore.floor');
    Route::get('embed/heatmap/{gameVersion}/{dungeon}', [SiteController::class, 'embedHeatmap'])->name('misc.embed.heatmap');
    Route::get('embed/heatmap/{gameVersion}/{dungeon}/{floorIndex}', [SiteController::class, 'embedHeatmap'])->name('misc.embed.heatmap.floor');
    Route::get('embed/{dungeonRoute}', [SiteController::class, 'embed'])->name('misc.embed');
    Route::get('status', [SiteController::class, 'status'])->name('misc.status');
    Route::middleware('throttle:oauth')->group(static function () {
        Route::get('login/google', [GoogleLoginController::class, 'redirectToProvider'])->name('login.google');
        Route::get('login/google/callback', [GoogleLoginController::class, 'handleProviderCallback'])->name('login.google.callback');
        Route::get('login/battlenet', [BattleNetLoginController::class, 'redirectToProvider'])->name('login.battlenet');
        Route::get('login/battlenet/callback', [BattleNetLoginController::class, 'handleProviderCallback'])->name('login.battlenet.callback');
        Route::get('login/discord', [DiscordLoginController::class, 'redirectToProvider'])->name('login.discord');
        Route::get('login/discord/callback', [DiscordLoginController::class, 'handleProviderCallback'])->name('login.discord.callback');
    });
    Route::get('new', [DungeonRouteController::class, 'create'])->name('dungeonroute.new');
    Route::get('new/temporary', [DungeonRouteController::class, 'createTemporary'])->name('dungeonroute.temporary.new');
    Route::middleware('throttle:create-dungeonroute')->group(static function () {
        Route::post('new', [DungeonRouteController::class, 'saveNew'])->middleware('auth')->name('dungeonroute.savenew');
        Route::post('new/temporary', [DungeonRouteController::class, 'saveNewTemporary'])->name('dungeonroute.temporary.savenew');
        Route::post('new/mdtimport', [MDTImportController::class, 'import'])->name('dungeonroute.new.mdtimport');
    });

    Route::get('patreon-oauth', [PatreonController::class, 'oauthRedirect'])->name('patreon.oauth.redirect');
    Route::get('dungeonroutes', [SiteController::class, 'dungeonroutes']);
    Route::middleware('throttle:search-dungeonroute')->group(static function () {
        Route::get('search', [DungeonRouteDiscoverController::class, 'search'])->name('dungeonroutes.search');
    });

    // Compendium
    Route::prefix('compendium')->middleware('game_version_compendium')->group(static function () {
        Route::get('/', [CompendiumController::class, 'index'])->name('compendium.index');
        // Every page whose content is scoped to a dungeon lives under the dungeon it is about.
        // The literal segment keeps the slug unambiguous, so no section here can ever be mistaken
        // for a dungeon (or the other way around) no matter what a dungeon is called.
        Route::prefix('dungeon/{dungeon}')->group(static function () {
            Route::get('npc', [NpcCompendiumController::class, 'indexDungeon'])->name('npc.compendium.index.dungeon');
            Route::get('spell', [SpellCompendiumController::class, 'indexDungeon'])->name('spell.compendium.index.dungeon');
            Route::get('activity', [NpcCompendiumController::class, 'activity'])->name('compendium.activity');
            Route::get('activity/{date}', [NpcCompendiumController::class, 'activityDay'])->name('compendium.activity.day');
            Route::get('tuning', [SpellTuningCompendiumController::class, 'indexDungeon'])->name('compendium.tuning');
            // withoutScopedBindings: a child binding with a custom key is scoped to its parent by
            // default, which would have Laravel resolve the class through $dungeon->characterClasses()
            Route::get('class/{characterClass:slug}', [ClassCompendiumController::class, 'showDungeon'])
                ->withoutScopedBindings()->name('compendium.class.show.dungeon');
        });
        Route::prefix('npc')->group(static function () {
            Route::get('/', [NpcCompendiumController::class, 'index'])->name('npc.compendium.index');
            Route::get('/{npc}', [NpcCompendiumController::class, 'show'])->name('npc.compendium.show');
        });
        Route::prefix('spell')->group(static function () {
            Route::get('/', [SpellCompendiumController::class, 'index'])->name('spell.compendium.index');
            Route::get('/{spell}', [SpellCompendiumController::class, 'show'])->name('spell.compendium.show');
        });
        Route::get('activity', [NpcCompendiumController::class, 'activityIndex'])->name('compendium.activity.index');
        Route::get('tuning', [SpellTuningCompendiumController::class, 'index'])->name('compendium.tuning.index');
        Route::prefix('class')->group(static function () {
            Route::get('/', [ClassCompendiumController::class, 'index'])->name('compendium.class.index');
            Route::get('/{characterClass:slug}', [ClassCompendiumController::class, 'show'])->name('compendium.class.show');
        });
    });

    // Game version toggle
    Route::prefix('gameversion')->group(static function () {
        Route::get('/{gameVersion}', [GameVersionController::class, 'update'])->name('gameversion.update');
    });

    // Game version toggle
    Route::prefix('dungeon')->group(static function () {
        Route::get('/{dungeon}', [DungeonController::class, 'changeContext'])->name('dungeon.changecontext');
    });

    // Creator directory
    Route::middleware(sprintf('feature_active:%s', CreatorProfiles::class))
        ->prefix('creators')->group(static function () {
            Route::get('/', [CreatorDirectoryController::class, 'index'])->name('creators.index');
        });

    // Publicly shared route collections
    Route::middleware(sprintf('feature_active:%s', CreatorProfiles::class))
        ->prefix('collection')->group(static function () {
            Route::get('/', [DungeonRouteCollectionController::class, 'browse'])->name('collection.browse');
            Route::get('/{dungeonRouteCollection}', [DungeonRouteCollectionController::class, 'view'])
                ->where('dungeonRouteCollection', '(?!new$)[^/]+')
                ->name('collection.view');
        });

    // Discover routes
    Route::prefix('routes')->group(static function () {
        Route::get('/', [DungeonRouteDiscoverController::class, 'discover'])->name('dungeonroutes');
        Route::get('/current', [DungeonRouteDiscoverController::class, 'discoverCurrentGameVersion'])->name('dungeonroutes.current');

        Route::prefix('/expansion/{expansion}')->group(static function () {
            Route::get('/', [DungeonRouteDiscoverController::class, 'discoverExpansion'])->name('dungeonroutes.expansion');
            Route::prefix('season/{season}')->group(static function () {
                Route::get('/', [DungeonRouteDiscoverExpansionSeasonController::class, 'discoverSeason'])->name('dungeonroutes.expansion.season');
                Route::get('popular', [DungeonRouteDiscoverExpansionSeasonController::class, 'discoverSeasonPopular'])->name('dungeonroutes.expansion.season.popular');
                Route::get('new', [DungeonRouteDiscoverExpansionSeasonController::class, 'discoverSeasonNew'])->name('dungeonroutes.expansion.season.new');
            });
        });

        Route::prefix('{gameVersion}')->group(static function () {
            Route::get('/', [DungeonRouteDiscoverController::class, 'discoverGameVersion'])->name('dungeonroutes.gameVersion');
            Route::get('popular', [DungeonRouteDiscoverController::class, 'discoverPopular'])->name('dungeonroutes.popular');
            Route::get('new', [DungeonRouteDiscoverController::class, 'discoverNew'])->name('dungeonroutes.new');
            Route::prefix('season/{season}')->group(static function () {
                Route::get('/', [DungeonRouteDiscoverController::class, 'discoverSeason'])->name('dungeonroutes.season');
                Route::get('popular', [DungeonRouteDiscoverController::class, 'discoverSeasonPopular'])->name('dungeonroutes.season.popular');
                Route::get('new', [DungeonRouteDiscoverController::class, 'discoverSeasonNew'])->name('dungeonroutes.season.new');
            });
            Route::prefix('{dungeon}')->group(static function () {
                Route::get('/', [DungeonRouteDiscoverController::class, 'discoverDungeon'])->name('dungeonroutes.discoverdungeon');
                Route::get('popular', [DungeonRouteDiscoverController::class, 'discoverDungeonPopular'])->name('dungeonroutes.discoverdungeon.popular');
                Route::get('new', [DungeonRouteDiscoverController::class, 'discoverDungeonNew'])->name('dungeonroutes.discoverdungeon.new');
            });
        });
    });

    Route::middleware('throttle:search-dungeonroute')->group(static function () {
        Route::prefix('dungeonroute/search')->group(static function () {
            Route::get('/', [DungeonRouteSearchController::class, 'search'])->name('dungeon.dungeonroute.search');

            Route::prefix('{gameVersion}')->group(static function () {
                Route::get('/', [DungeonRouteSearchController::class, 'searchByGameVersion'])->name('dungeon.dungeonroute.search.gameversion');
                Route::get('/select', [DungeonRouteSearchController::class, 'select'])->name('dungeon.dungeonroute.search.gameversion.select');
                Route::get('/{dungeon}', [DungeonRouteSearchController::class, 'searchByDungeon'])->name('dungeon.dungeonroute.search.gameversion.dungeon');
            });
        });
    });

    Route::prefix('heatmap')->group(static function () {
        Route::get('/', [DungeonHeatmapController::class, 'get'])->name('dungeon.heatmap');

        Route::prefix('{gameVersion}')->group(static function () {
            Route::get('/', [DungeonHeatmapController::class, 'getByGameVersion'])->name('dungeon.heatmap.gameversion');
            Route::get('/select', [DungeonHeatmapController::class, 'select'])->name('dungeon.heatmap.gameversion.select');
            Route::prefix('{dungeon}')->group(static function () {
                Route::get('/', [DungeonHeatmapController::class, 'viewDungeon'])->name('dungeon.heatmap.gameversion.view');
                Route::get('/embed', [DungeonHeatmapController::class, 'embed'])->name('dungeon.heatmap.gameversion.embed');
                Route::get('/embed/{floorIndex}', [DungeonHeatmapController::class, 'embed'])->name('dungeon.heatmap.gameversion.embed.floor');
                Route::get('/{floorIndex}', [DungeonHeatmapController::class, 'viewDungeonFloor'])->name('dungeon.heatmap.gameversion.view.floor');
            });
        });
    });

    // Explore dungeons (just show me the mapping but don't allow me to create routes)
    Route::prefix('explore')->group(static function () {
        Route::get('/', [DungeonExploreController::class, 'get'])->name('dungeon.explore');
        Route::get('/retail/mechagon-workshop', [DungeonExploreController::class, 'viewDungeonFloorMechagonWorkshopCorrection'])->name('dungeon.explore.gameversion.view.mechagonworkshopcorrection');
        Route::get('/retail/mechagon-workshop/{floorIndex}', [DungeonExploreController::class, 'viewDungeonFloorMechagonWorkshopCorrection'])->name('dungeon.explore.gameversion.view.mechagonworkshopcorrection.floor');
        Route::get('/retail/mechagon-workshop/embed', [DungeonExploreController::class, 'embedMechagonWorkshopCorrection'])->name('dungeon.explore.gameversion.embed.mechagonworkshopcorrection');
        Route::get('/retail/mechagon-workshop/embed/{floorIndex}', [DungeonExploreController::class, 'embedMechagonWorkshopCorrection'])->name('dungeon.explore.gameversion.embed.mechagonworkshopcorrection.floor');

        Route::prefix('{gameVersion}')->group(static function () {
            Route::get('/', [DungeonExploreController::class, 'getByGameVersion'])->name('dungeon.explore.gameversion');
            Route::get('/select', [DungeonExploreController::class, 'select'])->name('dungeon.explore.gameversion.select');
            Route::get('/start/{dungeonStart}', [DungeonExploreController::class, 'navigateDungeonStart'])->name('dungeon.explore.gameversion.start.navigate');
            Route::prefix('{dungeon}')->group(static function () {
                Route::get('/', [DungeonExploreController::class, 'viewDungeon'])->name('dungeon.explore.gameversion.view');
                Route::get('/embed', [DungeonExploreController::class, 'embed'])->name('dungeon.explore.gameversion.embed');
                Route::get('/embed/{floorIndex}', [DungeonExploreController::class, 'embed'])->name('dungeon.explore.gameversion.embed.floor');
                Route::get('/{floorIndex}', [DungeonExploreController::class, 'viewDungeonFloor'])->name('dungeon.explore.gameversion.view.floor');
            });
        });
    });

    // May be accessed without being logged in
    Route::get('team/invite/{invitecode}', [TeamController::class, 'invite'])->name('team.invite');
    Route::get('team/invite/{invitecode}/accept', [TeamController::class, 'inviteacceptredirect'])->name('team.invite.accept.redirect');

    // May not be logged in - we have anonymous routes
    Route::prefix('/route/{dungeon}/{dungeonroute}')->group(static function () {
        Route::get('/', [DungeonRouteController::class, 'view'])->name('dungeonroute.editnotitle');
        Route::prefix('{title?}')->group(static function () {
            // Upgrade the mapping of a route
            Route::post('upgrade', [DungeonRouteController::class, 'upgrade'])->name('dungeonroute.upgrade');
            // Edit your own dungeon routes
            Route::get('edit', [DungeonRouteController::class, 'edit'])->name('dungeonroute.edit');
            Route::get('edit/{floorIndex}', [DungeonRouteController::class, 'editFloor'])->name('dungeonroute.edit.floor');
            Route::middleware(['auth', 'role:user|admin'])->group(static function () {
                // Live sessions are only available for logged in users - for the synchronization stuff you MUST have a session
                Route::post('live', [LiveSessionController::class, 'create'])->name('dungeonroute.livesession.create');
                Route::get('live/{liveSession}', [LiveSessionController::class, 'view'])->name('dungeonroute.livesession.view');
                Route::get('live/{liveSession}/{floorIndex}', [LiveSessionController::class, 'viewFloor'])->name('dungeonroute.livesession.viewfloor');
                // Clone a route
                Route::post('clone', [DungeonRouteController::class, 'copy'])->name('dungeonroute.clone');
                // Claiming a route that was made by /sandbox functionality
                Route::post('claim', [DungeonRouteController::class, 'claim'])->name('dungeonroute.claim');
                Route::post('migrate/{seasonalType}', [DungeonRouteController::class, 'migrateToSeasonalType'])->name('dungeonroute.migrate');
                // Apply or discard a mapping version upgrade draft. Both take the DRAFT as their route
                // model - that is the page the buttons live on.
                Route::post('upgrade/apply', [DungeonRouteController::class, 'applyUpgrade'])->name('dungeonroute.upgrade.apply');
                Route::post('upgrade/discard', [DungeonRouteController::class, 'discardUpgrade'])->name('dungeonroute.upgrade.discard');
            });
        });
    });

    Route::middleware(['auth', 'role:user|admin'])->group(static function () {
        Route::get('patreon-link', [PatreonController::class, 'link'])->middleware('throttle:oauth')->name('patreon.link');
        Route::delete('patreon-unlink', [PatreonController::class, 'unlink'])->name('patreon.unlink');
        // Notifications
        Route::middleware(sprintf('feature_active:%s', Notifications::class))
            ->prefix('notifications')->group(static function () {
                Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
                Route::get('popover', [NotificationController::class, 'popover'])->name('notifications.popover');
                Route::post('read-all', [NotificationController::class, 'readAll'])->name('notifications.readall');
                Route::post('{notificationMessage}/read', [NotificationController::class, 'read'])->name('notifications.read');
                Route::post('{notificationMessage}/go', [NotificationController::class, 'go'])->name('notifications.go');
            });

        // Profile routes
        Route::prefix('profile')->group(static function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::get('routes', [ProfileController::class, 'routes'])->name('profile.routes');
            Route::get('favorites', [ProfileController::class, 'favorites'])->name('profile.favorites');
            Route::get('tags', [ProfileController::class, 'tags'])->name('profile.tags');
            // Must be declared before the patch('{user}') route below, which would otherwise match
            // 'creator' as a user and never reach this handler
            Route::middleware(sprintf('feature_active:%s', CreatorProfiles::class))
                ->patch('creator', [ProfileController::class, 'updateCreatorProfile'])->name('profile.creator.update');
            Route::middleware('throttle:change-password')->patch('{user}', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('delete', [ProfileController::class, 'delete'])->name('profile.delete');
            Route::patch('{user}/privacy', [ProfileController::class, 'updatePrivacy'])->name('profile.updateprivacy');
            Route::middleware('throttle:change-password')->patch('/', [ProfileController::class, 'changepassword'])->name('profile.changepassword');
            Route::middleware('throttle:create-tag')->group(static function () {
                Route::post('tag', [ProfileController::class, 'createTag'])->name('profile.tag.create');
            });
            Route::prefix('apitoken')->group(static function () {
                Route::middleware([sprintf('feature_active:%s', ApiTokens::class), 'throttle:create-api-token'])
                    ->post('/', [ProfileApiTokenController::class, 'store'])->name('profile.apitoken.store');
                // Not feature-gated: switching the feature off must never leave a live grant unrevocable
                Route::delete('{tokenId}', [ProfileApiTokenController::class, 'destroy'])->name('profile.apitoken.destroy');
            });
        });
        // Route collections
        Route::middleware(sprintf('feature_active:%s', CreatorProfiles::class))->group(static function () {
            Route::get('collections', [DungeonRouteCollectionController::class, 'index'])->name('collections.index');
            Route::prefix('collection')->group(static function () {
                Route::get('new', [DungeonRouteCollectionController::class, 'create'])->name('collection.new');
                Route::middleware('throttle:create-collection')->group(static function () {
                    Route::post('new', [DungeonRouteCollectionController::class, 'savenew'])->name('collection.savenew');
                });
                Route::prefix('{dungeonRouteCollection}')->group(static function () {
                    Route::get('edit', [DungeonRouteCollectionController::class, 'edit'])->name('collection.edit');
                    Route::patch('/', [DungeonRouteCollectionController::class, 'update'])->name('collection.update');
                    Route::delete('/', [DungeonRouteCollectionController::class, 'delete'])->name('collection.delete');
                    Route::middleware('throttle:create-collection')->group(static function () {
                        Route::post('duplicate', [DungeonRouteCollectionController::class, 'duplicate'])->name('collection.duplicate');
                    });
                });
            });
        });

        Route::get('teams', [TeamController::class, 'get'])->name('team.list');
        Route::prefix('team')->group(static function () {
            Route::get('new', [TeamController::class, 'create'])->name('team.new');
            Route::middleware('throttle:create-team')->group(static function () {
                Route::post('new', [TeamController::class, 'savenew'])->name('team.savenew');
            });
            Route::prefix('{team}')->group(static function () {
                Route::get('/', [TeamController::class, 'edit'])->name('team.edit');
                Route::delete('/', [TeamController::class, 'delete'])->name('team.delete');
                Route::patch('/', [TeamController::class, 'update'])->name('team.update');
                Route::middleware('throttle:create-tag')->group(static function () {
                    Route::post('tag/new', [TeamController::class, 'createTag'])->name('team.tag.create');
                });
            });
            Route::post('invite/{invitecode}/accept', [TeamController::class, 'inviteaccept'])->name('team.invite.accept');
        });
    });

    Route::middleware(['auth', 'role:admin'])->group(static function () {
        // Only admins may view a list of profiles
        Route::get('phpinfo', [SiteController::class, 'phpinfo'])->name('misc.phpinfo');
        Route::prefix('admin')->group(static function () {
            // Dungeons
            Route::prefix('dungeon')->group(static function () {
                Route::get('new', [DungeonController::class, 'create'])->name('admin.dungeon.new');
                Route::get('{dungeon}', [DungeonController::class, 'edit'])->name('admin.dungeon.edit');
                Route::post('new', [DungeonController::class, 'savenew'])->name('admin.dungeon.savenew');
                Route::patch('{dungeon}', [DungeonController::class, 'update'])->name('admin.dungeon.update');
                // Mapping versions
                Route::prefix('{dungeon}/mappingversion')->group(static function () {
                    Route::post('new', [MappingVersionController::class, 'saveNew'])->name('admin.mappingversion.new');
                    Route::delete('{mappingVersion}/delete', [MappingVersionController::class, 'delete'])->name('admin.mappingversion.delete');
                });
                // Floors
                Route::prefix('{dungeon}/floor')->group(static function () {
                    Route::get('new', [FloorController::class, 'create'])->name('admin.floor.new');
                    Route::post('new', [FloorController::class, 'savenew'])->name('admin.floor.savenew');
                    Route::prefix('{floor}')->group(static function () {
                        Route::get('/', [FloorController::class, 'edit'])->name('admin.floor.edit');
                        Route::patch('/', [FloorController::class, 'update'])->name('admin.floor.update');
                        Route::get('mapping', [FloorController::class, 'mapping'])->name('admin.floor.edit.mapping');
                        // Speedrun required npcs
                        Route::prefix('speedrunrequirednpcs')->whereIn('difficulty', DungeonDifficulty::values())->group(static function () {
                            Route::get('{difficulty}/new', [DungeonSpeedrunRequiredNpcsController::class, 'create'])->name('admin.dungeonspeedrunrequirednpc.new');
                            Route::post('{difficulty}/new', [DungeonSpeedrunRequiredNpcsController::class, 'createSave'])->name('admin.dungeonspeedrunrequirednpc.savenew');
                            Route::delete('{difficulty}/{dungeonspeedrunrequirednpc}', [DungeonSpeedrunRequiredNpcsController::class, 'delete'])->name('admin.dungeonspeedrunrequirednpc.delete');
                        });
                    });
                });
            });
            Route::get('dungeons', [DungeonController::class, 'get'])->name('admin.dungeons');
            // Expansions
            Route::prefix('expansion')->group(static function () {
                Route::get('new', [ExpansionController::class, 'create'])->name('admin.expansion.new');
                Route::get('{expansion}', [ExpansionController::class, 'edit'])->name('admin.expansion.edit');
                Route::post('new', [ExpansionController::class, 'savenew'])->name('admin.expansion.savenew');
                Route::patch('{expansion}', [ExpansionController::class, 'update'])->name('admin.expansion.update');
            });
            Route::get('expansions', [ExpansionController::class, 'get'])->name('admin.expansions');
            // Affixes
            Route::prefix('affix')->group(static function () {
                Route::get('new', [AffixController::class, 'create'])->name('admin.affix.new');
                Route::get('{affix}', [AffixController::class, 'edit'])->name('admin.affix.edit');
                Route::post('new', [AffixController::class, 'savenew'])->name('admin.affix.savenew');
                Route::patch('{affix}', [AffixController::class, 'update'])->name('admin.affix.update');
            });
            Route::get('affixes', [AffixController::class, 'get'])->name('admin.affixes');
            // Seasons
            Route::prefix('season')->group(static function () {
                Route::get('new', [SeasonController::class, 'create'])->name('admin.season.new');
                Route::get('{season}', [SeasonController::class, 'edit'])->name('admin.season.edit');
                Route::post('new', [SeasonController::class, 'savenew'])->name('admin.season.savenew');
                Route::patch('{season}', [SeasonController::class, 'update'])->name('admin.season.update');
                // Affix groups (rotation weeks)
                Route::prefix('{season}/affixgroup')->group(static function () {
                    Route::get('new', [AffixGroupController::class, 'create'])->name('admin.affixgroup.new');
                    Route::post('new', [AffixGroupController::class, 'savenew'])->name('admin.affixgroup.savenew');
                    Route::prefix('{affixGroup}')->group(static function () {
                        Route::get('/', [AffixGroupController::class, 'edit'])->name('admin.affixgroup.edit');
                        Route::patch('/', [AffixGroupController::class, 'update'])->name('admin.affixgroup.update');
                        Route::delete('/', [AffixGroupController::class, 'delete'])->name('admin.affixgroup.delete');
                    });
                });
            });
            Route::get('seasons', [SeasonController::class, 'get'])->name('admin.seasons');
            // NPCs
            Route::prefix('npc')->group(static function () {
                Route::get('new', [NpcController::class, 'create'])->name('admin.npc.new');
                Route::post('new', [NpcController::class, 'savenew'])->name('admin.npc.savenew');
                Route::prefix('{npc}')->group(static function () {
                    Route::get('/', [NpcController::class, 'edit'])->name('admin.npc.edit');
                    Route::patch('/', [NpcController::class, 'update'])->name('admin.npc.update');

                    Route::prefix('npcEnemyForces/')->group(static function () {
                        Route::get('new', [NpcEnemyForcesController::class, 'create'])->name('admin.npc.npcenemyforces.new');
                        Route::post('new', [NpcEnemyForcesController::class, 'savenew'])->name('admin.npc.npcenemyforces.savenew');

                        Route::prefix('{npcEnemyForces}')->group(static function () {
                            Route::delete('/', [NpcEnemyForcesController::class, 'delete'])->name('admin.npc.npcenemyforces.delete');
                            Route::get('/', [NpcEnemyForcesController::class, 'edit'])->name('admin.npc.npcenemyforces.edit');
                            Route::patch('/', [NpcEnemyForcesController::class, 'update'])->name('admin.npc.npcenemyforces.update');
                        });
                    });
                    Route::prefix('health/')->group(static function () {
                        Route::get('new', [NpcHealthController::class, 'create'])->name('admin.npc.npchealth.new');
                        Route::post('new', [NpcHealthController::class, 'savenew'])->name('admin.npc.npchealth.savenew');

                        Route::prefix('{npcHealth}')->group(static function () {
                            Route::delete('/', [NpcHealthController::class, 'delete'])->name('admin.npc.npchealth.delete');
                            Route::get('/', [NpcHealthController::class, 'edit'])->name('admin.npc.npchealth.edit');
                            Route::patch('/', [NpcHealthController::class, 'update'])->name('admin.npc.npchealth.update');
                        });
                    });
                });
            });
            Route::get('npcs', [NpcController::class, 'get'])->name('admin.npcs');
            // Spells
            Route::prefix('spell')->group(static function () {
                Route::get('new', [SpellController::class, 'create'])->name('admin.spell.new');
                Route::get('{spell}', [SpellController::class, 'edit'])->name('admin.spell.edit');
                Route::post('new', [SpellController::class, 'savenew'])->name('admin.spell.savenew');
                Route::patch('{spell}', [SpellController::class, 'update'])->name('admin.spell.update');
            });
            Route::get('spells', [SpellController::class, 'get'])->name('admin.spells');
            Route::prefix('user')->group(static function () {
                Route::delete('{user}/delete', [UserController::class, 'delete'])->name('admin.user.delete');
                // POST rather than GET: granting hands out paid benefits for free and now carries a required reason (#4385)
                Route::post('{user}/grantAllBenefits', [UserController::class, 'grantAllBenefits'])->name('admin.user.grantallbenefits');
            });
            Route::get('users', [UserController::class, 'get'])->name('admin.users');
            Route::get('userreports', [UserReportController::class, 'get'])->name('admin.userreports');
            // DungeonRoutes management
            Route::get('dungeonroutes', [AdminDungeonRouteController::class, 'index'])->name('admin.dungeonroutes');
            Route::prefix('dungeonroute')->group(static function () {
                Route::get('{dungeonRoute:id}', [AdminDungeonRouteController::class, 'edit'])->name('admin.dungeonroute.edit');
                Route::patch('{dungeonRoute:id}', [AdminDungeonRouteController::class, 'update'])->name('admin.dungeonroute.update');
                Route::delete('{dungeonRoute:id}', [AdminDungeonRouteController::class, 'destroy'])->name('admin.dungeonroute.delete');
                Route::post('{dungeonRoute:id}/claim', [AdminDungeonRouteController::class, 'claim'])->name('admin.dungeonroute.claim');
            });
            Route::prefix('tools')->group(static function () {
                Route::get('/', [AdminToolsController::class, 'index'])->name('admin.tools');

                Route::get('/messagebanner', [AdminToolsMessageBannerController::class, 'messageBanner'])->name('admin.tools.messagebanner.set');
                Route::post('/messagebanner', [AdminToolsMessageBannerController::class, 'messageBannerSubmit'])->name('admin.tools.messagebanner.set.submit');

                Route::get('/npc/import', [AdminToolsNpcController::class, 'npcimport'])->name('admin.tools.npc.import');
                Route::post('/npc/import', [AdminToolsNpcController::class, 'npcimportsubmit'])->name('admin.tools.npc.import.submit');
                Route::get('/npc/manage-spell-visibility/{dungeon?}', [AdminToolsNpcController::class, 'manageSpellVisibility'])->name('admin.tools.npc.managespellvisibility');
                Route::post('/npc/manage-spell-visibility/submit', [AdminToolsNpcController::class, 'manageSpellVisibilitySubmit'])->name('admin.tools.npc.managespellvisibility.submit');

                // Dungeonroute
                Route::get('/dungeonroute', [AdminToolsDungeonRouteController::class, 'dungeonroute'])->name('admin.tools.dungeonroute.view');
                Route::post('/dungeonroute', [AdminToolsDungeonRouteController::class, 'dungeonroutesubmit'])->name('admin.tools.dungeonroute.view.submit');
                Route::get('/dungeonroute/mappingversions', [AdminToolsDungeonRouteController::class, 'dungeonrouteMappingVersions'])->name('admin.tools.dungeonroute.mappingversionusage');
                Route::post('/dungeonroute/mappingversions/{mappingVersion}/upgrade', [AdminToolsDungeonRouteController::class, 'dungeonrouteMappingVersionsUpgrade'])->name('admin.tools.dungeonroute.mappingversionusage.upgrade');
                Route::get('/dungeonroute/generatetestroutes', [AdminToolsGenerateTestRoutesController::class, 'index'])->name('admin.tools.dungeonroute.generatetestroutes');
                Route::post('/dungeonroute/generatetestroutes/generate-batch', [AdminToolsGenerateTestRoutesController::class, 'generateBatch'])->name('admin.tools.dungeonroute.generatetestroutes.generate_batch');
                Route::post('/dungeonroute/generatetestroutes/delete-batch', [AdminToolsGenerateTestRoutesController::class, 'deleteBatch'])->name('admin.tools.dungeonroute.generatetestroutes.delete_batch');
                Route::get('/dungeonroute/{dungeonRoute:id}', [AdminToolsDungeonRouteController::class, 'dungeonrouteView'])->name('admin.tools.dungeonroute.view.get');

                // Enemy forces
                Route::get('enemyforces/recalculate', [AdminToolsEnemyForcesController::class, 'enemyforcesrecalculate'])->name('admin.tools.enemyforces.recalculate.view');
                Route::post('enemyforces/recalculate', [AdminToolsEnemyForcesController::class, 'enemyforcesrecalculatesubmit'])->name('admin.tools.enemyforces.recalculate.submit');

                // Thumbnails
                Route::get('thumbnails/regenerate', [AdminToolsThumbnailsController::class, 'thumbnailsregenerate'])->name('admin.tools.thumbnails.regenerate.view');
                Route::post('thumbnails/regenerate', [AdminToolsThumbnailsController::class, 'thumbnailsregeneratesubmit'])->name('admin.tools.thumbnails.regenerate.submit');

                // Combat log
                Route::get('combatlog/regenerate', [AdminToolsCombatLogController::class, 'combatlogregenerate'])->name('admin.tools.combatlog.regenerate.view');
                Route::post('combatlog/regenerate', [AdminToolsCombatLogController::class, 'combatlogregeneratesubmit'])->name('admin.tools.combatlog.regenerate.submit');
                Route::get('combatlog/route/enemy-failures', [AdminToolsCombatLogController::class, 'combatLogRouteEnemyFailures'])->name('admin.tools.combatlog.route.enemy_failures.view');
                Route::get('combatlog/route/enemy-failures/{floorIndex}', [AdminToolsCombatLogController::class, 'combatLogRouteEnemyFailuresFloor'])->name('admin.tools.combatlog.route.enemy_failures.view.floor');
                Route::get('combatlog/route/enemy-resolutions', [AdminToolsCombatLogController::class, 'combatLogRouteEnemyResolutions'])->name('admin.tools.combatlog.route.enemy_resolutions.view');
                Route::get('combatlog/route/enemy-resolutions/{floorIndex}', [AdminToolsCombatLogController::class, 'combatLogRouteEnemyResolutionsFloor'])->name('admin.tools.combatlog.route.enemy_resolutions.view.floor');
                Route::get('combatlog/route/coverage', [AdminToolsAutoRouteCoverageController::class, 'index'])->name('admin.tools.combatlog.route.coverage.view');
                Route::get('combatlog/criteria', [AdminToolsCombatLogCriteriaController::class, 'criteria'])->name('admin.tools.combatlog.criteria.view');
                Route::post('combatlog/criteria/reset', [AdminToolsCombatLogCriteriaController::class, 'criteriaReset'])->name('admin.tools.combatlog.criteria.reset');
                Route::post('combatlog/criteria/thresholds', [AdminToolsCombatLogCriteriaController::class, 'updateThresholds'])->name('admin.tools.combatlog.criteria.thresholds');
                Route::get('combatlog/rundata', [AdminToolsCombatLogRunDataController::class, 'index'])->name('admin.tools.combatlog.rundata');
                Route::post('combatlog/rundata/prune-batch', [AdminToolsCombatLogRunDataController::class, 'pruneBatch'])->name('admin.tools.combatlog.rundata.prune_batch');
                Route::prefix('mdt')->group(static function () {
                    // View string contents
                    Route::get('string', [AdminToolsMdtController::class, 'mdtview'])->name('admin.tools.mdt.string.view');
                    Route::post('string', [AdminToolsMdtController::class, 'mdtviewsubmit'])->name('admin.tools.mdt.string.submit');

                    // View string contents as a dungeonroute
                    Route::get('string/dungeonroute', [AdminToolsMdtController::class, 'mdtviewasdungeonroute'])->name('admin.tools.mdt.string.viewasdungeonroute');
                    Route::post('string/dungeonroute', [AdminToolsMdtController::class, 'mdtviewasdungeonroutesubmit'])->name('admin.tools.mdt.string.viewasdungeonroute.submit');

                    // View dungeonroute as string
                    Route::get('dungeonroute/string', [AdminToolsMdtController::class, 'mdtviewasstring'])->name('admin.tools.mdt.dungeonroute.viewasstring');
                    Route::post('dungeonroute/string', [AdminToolsMdtController::class, 'mdtviewasstringsubmit'])->name('admin.tools.mdt.dungeonroute.viewasstring.submit');

                    // View imported MDT strings
                    Route::get('string/list', [AdminToolsMdtController::class, 'mdtImportList'])->name('admin.tools.mdt.string.list');

                    // View mapping hash
                    Route::get('dungeonmappinghash', [AdminToolsMdtController::class, 'mdtdungeonmappinghash'])->name('admin.tools.mdt.dungeonmappinghash');
                    Route::post('dungeonmappinghash', [AdminToolsMdtController::class, 'mdtdungeonmappinghashsubmit'])->name('admin.tools.mdt.dungeonmappinghash.submit');

                    // Convert Mapping Version to MDT Mapping
                    Route::get('dungeonmappingversiontomdtmapping', [AdminToolsMdtController::class, 'dungeonmappingversiontomdtmapping'])->name('admin.tools.mdt.dungeonmappingversiontomdtmapping');
                    Route::post('dungeonmappingversiontomdtmapping', [AdminToolsMdtController::class, 'dungeonmappingversiontomdtmappingsubmit'])->name('admin.tools.mdt.dungeonmappingversiontomdtmapping.submit');

                    Route::get('dungeon/mappingversion/accuracy', [AdminToolsMdtController::class, 'dungeonMappingVersionAccuracy'])->name('admin.tools.mdt.dungeon_mapping_version_accuracy');
                });

                // Spells
                Route::get('spells/missingdata', [AdminToolsSpellsController::class, 'spellsShowMissingSpellInfo'])->name('admin.tools.spells.showmissingspellinfo');
                Route::get('spells/savetoseeder', [AdminToolsSpellsController::class, 'spellsSaveToSeeder'])->name('admin.tools.spells.savetoseeder');

                // NPCs
                Route::get('npcs/missingdisplayid', [AdminToolsNpcController::class, 'npcsShowMissingDisplayId'])->name('admin.tools.npcs.showmissingdisplayid');
                Route::get('npcs/savetoseeder', [AdminToolsNpcController::class, 'npcsSaveToSeeder'])->name('admin.tools.npcs.savetoseeder');

                // Wago.gg
                Route::get('wagogg/importingamecoordinates', [AdminToolsWagoGgController::class, 'wagoggImportIngameCoordinates'])->name('admin.tools.wagogg.import_ingame_coordinates');
                Route::post('wagogg/importingamecoordinates', [AdminToolsWagoGgController::class, 'wagoggImportIngameCoordinatesSubmit'])->name('admin.tools.wagogg.import_ingame_coordinates.submit');

                // Feature management
                Route::get('features', [AdminToolsFeaturesController::class, 'listFeatures'])->name('admin.tools.features.list');
                Route::post('features/toggle', [AdminToolsFeaturesController::class, 'toggleFeature'])->name('admin.tools.features.toggle');
                Route::post('features/forget', [AdminToolsFeaturesController::class, 'forgetFeature'])->name('admin.tools.features.forget');

                // Artisan commands
                Route::get('artisan-commands/backfill-kill-zone-enemy-id', [AdminToolsArtisanCommandsController::class, 'backfillKillZoneEnemyId'])->name('admin.tools.artisancommands.backfillkillzoneenemyid.view');
                Route::post('artisan-commands/run', [AdminToolsArtisanCommandsController::class, 'run'])->name('admin.tools.artisancommands.run');

                // Exception thrower
                Route::get('exception', [AdminToolsExceptionController::class, 'exceptionselect'])->name('admin.tools.exception.select');
                Route::post('exception', [AdminToolsExceptionController::class, 'exceptionselectsubmit'])->name('admin.tools.exception.select.submit');
                Route::get('mdt/diff', [AdminToolsMdtController::class, 'mdtdiff'])->name('admin.tools.mdt.diff');
                Route::post('cache/drop', [AdminToolsController::class, 'dropcache'])->name('admin.tools.cache.drop');
                Route::post('datadump/exportdungeondata', [AdminToolsDataDumpController::class, 'exportdungeondata'])->name('admin.tools.datadump.exportdungeondata');
                Route::post('readonly/toggle', [AdminToolsController::class, 'toggleReadOnlyMode'])->name('admin.tools.readonly.toggle');
                Route::post('thumbnails/toggle', [AdminToolsController::class, 'toggleThumbnailGeneration'])->name('admin.tools.thumbnails.toggle');

                // Telemetry
                Route::get('telemetry', [AdminToolsTelemetryController::class, 'index'])->name('admin.tools.telemetry.view');
                Route::get('telemetry/data', [AdminToolsTelemetryController::class, 'data'])->name('admin.tools.telemetry.data');

                // Banned IP addresses
                Route::get('patreon/grants', [AdminToolsPatreonGrantsController::class, 'index'])->name('admin.tools.patreon.grants.view');
                Route::delete('patreon/grants/{user}', [AdminToolsPatreonGrantsController::class, 'revoke'])->name('admin.tools.patreon.grants.revoke');

                // Notifications
                Route::get('notifications', [AdminToolsNotificationController::class, 'index'])->name('admin.tools.notifications.view');
                Route::post('notifications', [AdminToolsNotificationController::class, 'store'])->name('admin.tools.notifications.store');
                Route::delete('notifications/{notificationMessage}', [AdminToolsNotificationController::class, 'destroy'])->name('admin.tools.notifications.destroy');

                Route::get('bannedipaddresses', [AdminToolsBannedIpAddressController::class, 'index'])->name('admin.tools.bannedipaddresses.view');
                Route::post('bannedipaddresses', [AdminToolsBannedIpAddressController::class, 'store'])->name('admin.tools.bannedipaddresses.store');
                Route::delete('bannedipaddresses/{bannedIpAddress}', [AdminToolsBannedIpAddressController::class, 'destroy'])->name('admin.tools.bannedipaddresses.destroy');
            });
        });
    });

    Route::prefix('ajax')->middleware('ajax')->group(static function () {
        Route::get('refresh-csrf', [AjaxSiteController::class, 'refreshCsrf'])->name('api.refresh_csrf');
        Route::get('view/{view}', [AjaxViewController::class, 'view'])->name('ajax.view');

        Route::prefix('tag')->group(static function () {
            Route::middleware('throttle:create-tag')->group(static function () {
                Route::post('/', [AjaxTagController::class, 'store'])->name('ajax.tag.create');
                Route::delete('/{tag}', [AjaxTagController::class, 'delete'])->name('ajax.tag.delete');

                Route::put('/{tag}/all', [AjaxTagController::class, 'updateAll'])->name('ajax.tag.updateall');
                Route::delete('/{tag}/all', [AjaxTagController::class, 'deleteAll'])->name('ajax.tag.deleteall');
            });
        });
        Route::middleware(sprintf('feature_active:%s', WalkGraphLayer::class))->group(static function () {
            Route::get('/floor/{floor}/walkgraph', [AjaxWalkGraphController::class, 'show'])->name('ajax.floor.walkgraph');
            Route::get('/floor/{floor}/walkgraph/area', [AjaxWalkGraphController::class, 'area'])->name('ajax.floor.walkgraph.area');
        });

        Route::prefix('heatmap')->middleware('throttle:heatmap-data')->group(static function () {
            Route::get('/data', [AjaxHeatmapController::class, 'getData'])->name('ajax.heatmap.data');
        });

        Route::middleware('throttle:create-reports')->group(static function () {
            Route::post('userreport/dungeonroute/{dungeonroute}', [AjaxUserReportController::class, 'dungeonrouteStore'])->name('ajax.userreport.dungeonroute');
            Route::post('userreport/enemy/{enemy}', [AjaxUserReportController::class, 'enemyStore'])->name('ajax.userreport.enemy');
        });

        Route::middleware('throttle:search-dungeonroute')->group(static function () {
            Route::get('/routes', [AjaxDungeonRouteController::class, 'get']);

            Route::get('/search/{category}', [AjaxDungeonRouteController::class, 'htmlsearchcategory']);

            Route::get('/dungeonroute/{dungeonRoute}/mapcontext', [AjaxDungeonRouteSearchController::class, 'getMapContext']);
            Route::post('/dungeonroute/search/{gameVersion}/{dungeon}', [AjaxDungeonRouteSearchController::class, 'get']);
        });

        Route::middleware(['auth', 'role:user|admin', 'throttle:delete-dungeonroutes'])->group(static function () {
            Route::delete('/routes', [AjaxDungeonRouteController::class, 'deleteBulk'])->name('api.dungeonroute.delete.bulk');
        });

        Route::middleware('throttle:mdt-details')->group(static function () {
            Route::post('/mdt/details', [MDTImportController::class, 'details'])->name('mdt.details');
        });

        // legalAgree only mutates the caller's own record, so plain 'auth' is enough - a role-less
        // authenticated user (e.g. the seeded Internal Team account) must still be able to clear the
        // legal modal, which re-shows on every page until legal_agreed is set.
        Route::middleware('auth')->group(static function () {
            Route::post('/profile/legal', [AjaxProfileController::class, 'legalAgree']);
        });
        Route::middleware(['auth', 'role:user|admin'])->group(static function () {
            Route::post('/profile/adfree/{user:public_key}', [AjaxProfileController::class, 'addAdFreeGiveaway']);
            Route::delete('/profile/adfree/{user:public_key}', [AjaxProfileController::class, 'removeAdFreeGiveaway']);

            Route::middleware(sprintf('feature_active:%s', CreatorProfiles::class))
                ->get('/collections', [AjaxDungeonRouteCollectionController::class, 'forDungeonRoute'])
                ->name('ajax.collections.fordungeonroute');
            Route::middleware(sprintf('feature_active:%s', CreatorProfiles::class))
                ->prefix('collection/{dungeonRouteCollection}/routes')->group(static function () {
                    Route::post('/', [AjaxDungeonRouteCollectionController::class, 'storeRoutes'])->name('ajax.collection.routes.store');
                    Route::delete('/', [AjaxDungeonRouteCollectionController::class, 'deleteRoutes'])->name('ajax.collection.routes.delete');
                    Route::put('/order', [AjaxDungeonRouteCollectionController::class, 'updateRoutesOrder'])->name('ajax.collection.routes.order');
                    Route::post('/publish', [AjaxDungeonRouteCollectionController::class, 'publishRoutes'])->name('ajax.collection.routes.publish');
                });
        });

        // Metrics
        Route::middleware('throttle:store-metric')->prefix('metric')->group(static function () {
            Route::post('/', [AjaxMetricController::class, 'store'])->name('ajax.metric.store');
            Route::post('/route/{dungeonRoute}', [AjaxMetricController::class, 'storeDungeonRoute'])->name('ajax.metric.dungeonroute.store');
        });

        // Compendium
        Route::prefix('compendium')->middleware('game_version_compendium')->group(static function () {
            Route::prefix('npc')->group(static function () {
                Route::get('/', [NpcCompendiumController::class, 'get'])->name('ajax.npc.compendium.search');
            });
            Route::prefix('spell')->group(static function () {
                Route::get('/', [SpellCompendiumController::class, 'get'])->name('ajax.spell.compendium.search');
            });
        });

        // Must be an admin to perform these actions
        Route::middleware(['auth', 'role:admin'])->group(static function () {
            Route::prefix('admin')->group(static function () {
                Route::get('/user', [AjaxUserController::class, 'get']);
                Route::post('/user/{user}/role', [AjaxUserController::class, 'updateRole'])->name('ajax.admin.user.role.update');
                Route::get('/npc', [AjaxNpcController::class, 'get']);
                Route::post('/thumbnail/{dungeonroute}/refresh', [AjaxDungeonRouteController::class, 'refreshThumbnail']);

                Route::put('/spell/{spell}', [AjaxSpellController::class, 'update']);

                Route::prefix('mappingVersion/{mappingVersion}')->scopeBindings()->group(static function () {
                    Route::patch('/', [AjaxMappingVersionController::class, 'store']);
                    Route::post('/enemy', [AjaxEnemyController::class, 'store']);
                    Route::put('/enemy/{enemy}', [AjaxEnemyController::class, 'store']);
                    Route::delete('/enemy/{enemy}', [AjaxEnemyController::class, 'delete']);

                    Route::post('/enemypack', [AjaxEnemyPackController::class, 'store'])->name('ajax.admin.enemypack.create');
                    Route::put('/enemypack/{enemyPack}', [AjaxEnemyPackController::class, 'store'])->name('ajax.admin.enemypack.update');
                    Route::delete('/enemypack/{enemyPack}', [AjaxEnemyPackController::class, 'delete'])->name('ajax.admin.enemypack.delete');

                    Route::post('/enemypatrol', [AjaxEnemyPatrolController::class, 'store'])->name('ajax.admin.enemypatrol.create');
                    Route::put('/enemypatrol/{enemyPatrol}', [AjaxEnemyPatrolController::class, 'store'])->name('ajax.admin.enemypatrol.update');
                    Route::delete('/enemypatrol/{enemyPatrol}', [AjaxEnemyPatrolController::class, 'delete'])->name('ajax.admin.enemypatrol.delete');

                    Route::post('/dungeonfloorswitchmarker', [AjaxDungeonFloorSwitchMarkerController::class, 'store']);
                    Route::put('/dungeonfloorswitchmarker/{dungeonFloorSwitchMarker}', [AjaxDungeonFloorSwitchMarkerController::class, 'store']);
                    Route::delete('/dungeonfloorswitchmarker/{dungeonFloorSwitchMarker}', [AjaxDungeonFloorSwitchMarkerController::class, 'delete']);

                    Route::post('/dungeonstart', [AjaxDungeonStartController::class, 'store'])->name('ajax.admin.dungeonstart.create');
                    Route::put('/dungeonstart/{dungeonStart}', [AjaxDungeonStartController::class, 'store'])->name('ajax.admin.dungeonstart.update');
                    Route::delete('/dungeonstart/{dungeonStart}', [AjaxDungeonStartController::class, 'delete'])->name('ajax.admin.dungeonstart.delete');

                    Route::post('/dungeontransport', [AjaxDungeonTransportController::class, 'store'])->name('ajax.admin.dungeontransport.create');
                    Route::put('/dungeontransport/{dungeonTransport}', [AjaxDungeonTransportController::class, 'store'])->name('ajax.admin.dungeontransport.update');
                    Route::delete('/dungeontransport/{dungeonTransport}', [AjaxDungeonTransportController::class, 'delete'])->name('ajax.admin.dungeontransport.delete');

                    Route::post('/mapicon', [AjaxMapIconController::class, 'adminStore']);
                    Route::put('/mapicon/{mapIcon}', [AjaxMapIconController::class, 'adminStore']);
                    Route::delete('/mapicon/{mapIcon}', [AjaxMapIconController::class, 'adminDelete']);

                    Route::post('/mountablearea', [AjaxMountableAreaController::class, 'store'])->name('ajax.admin.mountablearea.create');
                    Route::put('/mountablearea/{mountableArea}', [AjaxMountableAreaController::class, 'store'])->name('ajax.admin.mountablearea.update');
                    Route::delete('/mountablearea/{mountableArea}', [AjaxMountableAreaController::class, 'delete'])->name('ajax.admin.mountablearea.delete');

                    Route::post('/enemyforcescheckpoint', [AjaxEnemyForcesCheckpointController::class, 'store'])->name('ajax.admin.enemyforcescheckpoint.create');
                    Route::put('/enemyforcescheckpoint/{enemyForcesCheckpoint}', [AjaxEnemyForcesCheckpointController::class, 'store'])->name('ajax.admin.enemyforcescheckpoint.update');
                    Route::delete('/enemyforcescheckpoint/{enemyForcesCheckpoint}', [AjaxEnemyForcesCheckpointController::class, 'delete'])->name('ajax.admin.enemyforcescheckpoint.delete');

                    Route::post('/floorunion', [AjaxFloorUnionController::class, 'store']);
                    Route::put('/floorunion/{floorUnion}', [AjaxFloorUnionController::class, 'store']);
                    Route::delete('/floorunion/{floorUnion}', [AjaxFloorUnionController::class, 'delete']);

                    Route::post('/floorunionarea', [AjaxFloorUnionAreaController::class, 'store'])->name('ajax.admin.floorunionarea.create');
                    Route::put('/floorunionarea/{floorUnionArea}', [AjaxFloorUnionAreaController::class, 'store'])->name('ajax.admin.floorunionarea.update');
                    Route::delete('/floorunionarea/{floorUnionArea}', [AjaxFloorUnionAreaController::class, 'delete'])->name('ajax.admin.floorunionarea.delete');
                });

                Route::prefix('combatlogroute')->group(static function () {
                    Route::get('/enemy-failures', [AjaxAdminCombatLogRouteController::class, 'getEnemyFailures'])
                        ->name('ajax.admin.combatlogroute.enemy_failures');
                    Route::get('/enemy-failures/clusters', [AjaxAdminCombatLogRouteController::class, 'getEnemyFailureClusters'])
                        ->name('ajax.admin.combatlogroute.enemy_failures.clusters');
                    Route::delete('/enemy-failures', [AjaxAdminCombatLogRouteController::class, 'deleteEnemyFailures'])
                        ->name('ajax.admin.combatlogroute.enemy_failures.delete');
                    Route::get('/enemy-resolutions', [AjaxAdminCombatLogRouteController::class, 'getEnemyResolutions'])
                        ->name('ajax.admin.combatlogroute.enemy_resolutions');
                    Route::get('/enemy-resolutions/lines', [AjaxAdminCombatLogRouteController::class, 'getEnemyResolutionLines'])
                        ->name('ajax.admin.combatlogroute.enemy_resolutions.lines');
                    Route::get('/enemy-resolutions/groups', [AjaxAdminCombatLogRouteController::class, 'getEnemyResolutionGroups'])
                        ->name('ajax.admin.combatlogroute.enemy_resolutions.groups');
                    Route::delete('/enemy-resolutions', [AjaxAdminCombatLogRouteController::class, 'deleteEnemyResolutions'])
                        ->name('ajax.admin.combatlogroute.enemy_resolutions.delete');
                });
            });
            Route::put('/userreport/{userreport}/status', [AjaxUserReportController::class, 'status']);
            Route::post('/tools/mdt/diff/apply', [AdminToolsMdtController::class, 'applyChange']);
            Route::put('/user/{user}/patreon/benefits', [UserController::class, 'storePatreonBenefits']);
        });

        // May be performed without being logged in (sandbox functionality)
        Route::prefix('{dungeonRoute}')->group(static function () {
            Route::get('/brushline/{brushline}', [AjaxBrushlineController::class, 'show'])->name('ajax.dungeonroute.brushline.show');
            Route::get('/killzone/paths', [AjaxKillZoneController::class, 'paths']);
            Route::get('/path/{path}', [AjaxPathController::class, 'show'])->name('ajax.dungeonroute.path.show');
            Route::get('/arrow/{arrow}', [AjaxArrowController::class, 'show'])->name('ajax.dungeonroute.arrow.show');

            Route::middleware('throttle:edit-dungeonroute')->group(static function () {
                Route::post('/brushline', [AjaxBrushlineController::class, 'store'])->name('ajax.dungeonroute.brushline.create');
                Route::put('/brushline/{brushline}', [AjaxBrushlineController::class, 'store'])->name('ajax.dungeonroute.brushline.update');
                Route::delete('/brushline/{brushline}', [AjaxBrushlineController::class, 'delete'])->name('ajax.dungeonroute.brushline.delete');

                Route::put('/killzone/mass', [AjaxKillZoneController::class, 'storeAll']);
                Route::post('/killzone', [AjaxKillZoneController::class, 'store']);
                Route::put('/killzone/{killZone}', [AjaxKillZoneController::class, 'store']);
                Route::delete('/killzone/{killZone}', [AjaxKillZoneController::class, 'delete']);
                Route::delete('/killzone', [AjaxKillZoneController::class, 'deleteAll']);

                Route::post('/mapicon', [AjaxMapIconController::class, 'dungeonRouteStore']);
                Route::put('/mapicon/{mapIcon}', [AjaxMapIconController::class, 'dungeonRouteStore']);
                Route::delete('/mapicon/{mapIcon}', [AjaxMapIconController::class, 'delete']);

                Route::post('/path', [AjaxPathController::class, 'store'])->name('ajax.dungeonroute.path.create');
                Route::put('/path/{path}', [AjaxPathController::class, 'store'])->name('ajax.dungeonroute.path.update');
                Route::delete('/path/{path}', [AjaxPathController::class, 'delete'])->name('ajax.dungeonroute.path.delete');

                Route::post('/arrow', [AjaxArrowController::class, 'store'])->name('ajax.dungeonroute.arrow.create');
                Route::put('/arrow/{arrow}', [AjaxArrowController::class, 'store'])->name('ajax.dungeonroute.arrow.update');
                Route::delete('/arrow/{arrow}', [AjaxArrowController::class, 'delete'])->name('ajax.dungeonroute.arrow.delete');

                Route::post('/raidmarker/{enemy}', [AjaxEnemyController::class, 'setRaidMarker']);
            });

            Route::middleware('throttle:create-dungeonroute')->group(static function () {
                Route::post('/clone/team/{team}', [AjaxDungeonRouteController::class, 'cloneToTeam']);
                Route::post('/continue', [AjaxDungeonRouteController::class, 'continueInNewerSeason'])->name('ajax.dungeonroute.continue');
            });

            // The signature is relative: it covers the path and query string only, so whichever
            // scheme/host the ALB and CloudFlare hand us can never invalidate it
            Route::middleware(['throttle:mdt-export', 'signed:relative'])->group(static function () {
                Route::get('/mdtExport', [AjaxDungeonRouteController::class, 'mdtExport'])->name('api.dungeonroute.mdtexport');
            });

            Route::middleware('throttle:simulate')->group(static function () {
                Route::post('/simulate', [AjaxDungeonRouteController::class, 'simulate'])->name('api.dungeonroute.simulate');
            });
        });

        // Must be logged in to perform these actions
        Route::middleware(['auth', 'role:user|admin'])->group(static function () {
            Route::prefix('{dungeonRoute}')->group(static function () {
                Route::patch('/', [AjaxDungeonRouteController::class, 'store'])->name('api.dungeonroute.update');
                Route::patch('/pullgradient', [AjaxDungeonRouteController::class, 'storePullGradient'])->name('api.dungeonroute.pullgradient.update');
                Route::delete('/', [AjaxDungeonRouteController::class, 'delete'])->name('api.dungeonroute.delete');
                Route::post('/favorite', [AjaxDungeonRouteController::class, 'favorite'])->name('api.dungeonroute.favorite');
                Route::delete('/favorite', [AjaxDungeonRouteController::class, 'favoriteDelete'])->name('api.dungeonroute.favorite.delete');

                Route::post('/publishedState', [AjaxDungeonRouteController::class, 'publishedState'])->name('api.dungeonroute.publishedstate');

                Route::put('/scheduledPublish', [AjaxDungeonRouteController::class, 'storeScheduledPublish'])->name('api.dungeonroute.scheduledpublish.store');
                Route::delete('/scheduledPublish', [AjaxDungeonRouteController::class, 'destroyScheduledPublish'])->name('api.dungeonroute.scheduledpublish.destroy');

                Route::post('/rate', [AjaxDungeonRouteController::class, 'rate'])->name('api.dungeonroute.rate');
                Route::delete('/rate', [AjaxDungeonRouteController::class, 'rateDelete'])->name('api.dungeonroute.rate.delete');

                Route::post('/migrate/{seasonalType}', [AjaxDungeonRouteController::class, 'migrateToSeasonalType']);

                Route::prefix('/live/{liveSession}')->group(static function () {
                    Route::delete('/', [AjaxLiveSessionController::class, 'delete']);
                    Route::get('/state', [AjaxLiveSessionController::class, 'state']);
                    Route::post('/overpulledenemy', [AjaxOverpulledEnemyController::class, 'store']);
                    Route::delete('/overpulledenemy', [AjaxOverpulledEnemyController::class, 'delete']);
                });
            });
            // Teams
            Route::prefix('team/{team}')->group(static function () {
                Route::put('/changedefaultrole', [AjaxTeamController::class, 'changeDefaultRole']);
                Route::put('/changerole', [AjaxTeamController::class, 'changeRole']);
                Route::post('/route', [AjaxTeamController::class, 'addRoutes']);
                Route::post('/route/{dungeonroute}', [AjaxTeamController::class, 'addRoute']);
                Route::delete('/member/{user}', [AjaxTeamController::class, 'removeMember']);
                Route::delete('/route/{dungeonroute}', [AjaxTeamController::class, 'removeRoute']);
                Route::post('/refreshlink', [AjaxTeamController::class, 'refreshInviteLink']);
                // Ad-free giveaway
                Route::post('/member/{user}/adfree', [AjaxTeamController::class, 'addAdFreeGiveaway']);
                Route::delete('/member/{user}/adfree', [AjaxTeamController::class, 'removeAdFreeGiveaway']);
            });
            // User
            Route::prefix('user/{publicKey}')->group(static function () {
                Route::put('/', [AjaxUserController::class, 'store']);
            });
        });
    });

    Route::get('user/{user:slug}', [ProfileController::class, 'view'])->name('profile.view');
    Route::middleware(sprintf('feature_active:%s', CreatorProfiles::class))
        ->scopeBindings()
        ->get('user/{user:slug}/link/{socialLink}', [ProfileController::class, 'leaveToSocialLink'])
        ->name('profile.sociallink.leave');
    // At the bottom to let routes such as profile/routes pass through first
    Route::get('profile/{user}', [ProfileController::class, 'viewLegacy'])->whereNumber('user')->name('profile.view.legacy');

    // View any dungeon route (catch all)
    Route::prefix('/route/{dungeon}/{dungeonroute}')->group(static function () {
        Route::get('/', [DungeonRouteController::class, 'view'])->name('dungeonroute.viewnotitle');
        Route::prefix('{title?}')->group(static function () {
            Route::get('/', [DungeonRouteController::class, 'view'])->name('dungeonroute.view');
            Route::get('present/', [DungeonRouteController::class, 'present'])->name('dungeonroute.present');
            Route::get('present/{floorIndex}', [DungeonRouteController::class, 'presentFloor'])->name('dungeonroute.present.floor');
            Route::get('embed/', [DungeonRouteController::class, 'embed'])->name('dungeonroute.embed');
            Route::get('embed/{floorIndex}', [DungeonRouteController::class, 'embed'])->name('dungeonroute.embed.floor');
            Route::get('{floorIndex}', [DungeonRouteController::class, 'viewFloor'])->name('dungeonroute.view.floor');
            // Preview of a route for image capturing library
            Route::get('preview/{floorIndex}', [DungeonRouteController::class, 'preview'])->name('dungeonroute.preview');
        });
    });

    Route::prefix('{dungeonRoute}')->group(static function () {
        // Edit your own dungeon routes
        Route::get('edit', [DungeonRouteLegacyController::class, 'edit'])->name('dungeonroute.editold');
        // Legacy edit floor route
        Route::get('edit/{floorIndex}', [DungeonRouteLegacyController::class, 'editFloor'])
            ->name('dungeonroute.editold.floor')
            ->where('floorIndex', '[0-9]+');
        Route::middleware(['auth', 'role:user|admin'])->group(static function () {
            // Live sessions are only available for logged in users - for the synchronization stuff you MUST have a session
            Route::get('live/{liveSession}', [LiveSessionLegacyController::class, 'view'])->name('dungeonroute.livesession.viewold');
            Route::get('live/{liveSession}/{floorIndex}', [LiveSessionLegacyController::class, 'viewFloor'])->name('dungeonroute.livesession.viewfloorold');
            // Clone a route
            Route::middleware('throttle:create-dungeonroute')->group(static function () {
                Route::post('clone', [DungeonRouteLegacyController::class, 'cloneOld'])->name('dungeonroute.cloneold');
            });
            // Claiming a route that was made by /sandbox functionality
            Route::post('claim', [DungeonRouteLegacyController::class, 'claimOld'])->name('dungeonroute.claimold');
        });

        Route::get('/', [DungeonRouteLegacyController::class, 'viewOld'])->name('dungeonroute.viewold');
        Route::get('embed/', [DungeonRouteLegacyController::class, 'embedOld'])->name('dungeonroute.embedold');
        Route::get('embed/{floorIndex}', [DungeonRouteLegacyController::class, 'embedOld'])
            ->name('dungeonroute.embedold.floor')
            ->where('floorIndex', '[0-9]+');
        Route::get('{floorIndex}', [DungeonRouteLegacyController::class, 'viewFloorOld'])
            ->name('dungeonroute.viewold.floor')
            ->where('floorIndex', '[0-9]+');
        // Preview of a route for image capturing library
        Route::get('preview/{floorIndex}', [DungeonRouteLegacyController::class, 'previewOld'])
            ->name('dungeonroute.previewold')
            ->where('floorIndex', '[0-9]+');
    });
});

Route::fallback(
    // Reaching the fallback means no route matched this request. The fallback itself is a GET/HEAD
    // route, so it only ever fires for GET/HEAD requests; if the URI is served under other verbs the
    // request is a 405 (advertising the allowed verbs), otherwise it is a genuine 404. Aborting - as
    // opposed to rendering a view inline - keeps the response mapping in App\Exceptions\Handler, while
    // the 'web' middleware keeps the session active so the rendered error page shows a logged-in nav.
    static function (Request $request): void {
        $otherVerbs = collect(Route::getRoutes()->getRoutes())
            ->filter(static fn(RoutingRoute $route): bool => !$route->isFallback && $route->matches($request, false))
            ->flatMap(static fn(RoutingRoute $route): array => $route->methods())
            ->unique()
            ->reject(static fn(string $method): bool => in_array($method, [Request::METHOD_GET, Request::METHOD_HEAD], true))
            ->values();

        if ($otherVerbs->isNotEmpty()) {
            throw new MethodNotAllowedHttpException($otherVerbs->all());
        }

        abort(Response::HTTP_NOT_FOUND);
    },
)->middleware('web');
