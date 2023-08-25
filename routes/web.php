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

use App\Http\Controllers\AdminToolsController;
use App\Http\Controllers\Ajax\AjaxBrushlineController;
use App\Http\Controllers\Ajax\AjaxDungeonFloorSwitchMarkerController;
use App\Http\Controllers\Ajax\AjaxDungeonRouteController;
use App\Http\Controllers\Ajax\AjaxEchoController;
use App\Http\Controllers\Ajax\AjaxEnemyController;
use App\Http\Controllers\Ajax\AjaxEnemyPackController;
use App\Http\Controllers\Ajax\AjaxEnemyPatrolController;
use App\Http\Controllers\Ajax\AjaxKillZoneController;
use App\Http\Controllers\Ajax\AjaxLiveSessionController;
use App\Http\Controllers\Ajax\AjaxMapIconController;
use App\Http\Controllers\Ajax\AjaxMappingVersionController;
use App\Http\Controllers\Ajax\AjaxMetricController;
use App\Http\Controllers\Ajax\AjaxMountableAreaController;
use App\Http\Controllers\Ajax\AjaxNpcController;
use App\Http\Controllers\Ajax\AjaxOverpulledEnemyController;
use App\Http\Controllers\Ajax\AjaxPathController;
use App\Http\Controllers\Ajax\AjaxPridefulEnemyController;
use App\Http\Controllers\Ajax\AjaxProfileController;
use App\Http\Controllers\Ajax\AjaxSiteController;
use App\Http\Controllers\Ajax\AjaxTagController;
use App\Http\Controllers\Ajax\AjaxTeamController;
use App\Http\Controllers\Ajax\AjaxUserController;
use App\Http\Controllers\Ajax\AjaxUserReportController;
use App\Http\Controllers\Auth\BattleNetLoginController;
use App\Http\Controllers\Auth\DiscordLoginController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Dungeon\DungeonExploreController;
use App\Http\Controllers\Dungeon\MappingVersionController;
use App\Http\Controllers\DungeonController;
use App\Http\Controllers\DungeonRouteController;
use App\Http\Controllers\DungeonRouteDiscoverController;
use App\Http\Controllers\DungeonRouteLegacyController;
use App\Http\Controllers\ExpansionController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\GameVersionController;
use App\Http\Controllers\LiveSessionController;
use App\Http\Controllers\LiveSessionLegacyController;
use App\Http\Controllers\MDTImportController;
use App\Http\Controllers\NpcController;
use App\Http\Controllers\NpcEnemyForcesController;
use App\Http\Controllers\PatreonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Speedrun\DungeonSpeedrunRequiredNpcsController;
use App\Http\Controllers\SpellController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserReportController;
use App\Http\Controllers\WebhookController;

Auth::routes();

// Webhooks
Route::post('webhook/github', [WebhookController::class, 'github'])->name('webhook.github');

Route::group(['middleware' => ['viewcachebuster', 'language', 'debugbarmessagelogger', 'read_only_mode', 'debug_info_context_logger']], function () {
    // Catch for hard-coded /home route in RedirectsUsers.php
    Route::get('home', [SiteController::class, 'home']);

    Route::get('credits', [SiteController::class, 'credits'])->name('misc.credits');

    Route::get('about', [SiteController::class, 'about'])->name('misc.about');

    Route::get('privacy', [SiteController::class, 'privacy'])->name('legal.privacy');

    Route::get('terms', [SiteController::class, 'terms'])->name('legal.terms');

    Route::get('cookies', [SiteController::class, 'cookies'])->name('legal.cookies');

    Route::get('/', [SiteController::class, 'index'])->name('home');

    Route::get('changelog', [SiteController::class, 'changelog'])->name('misc.changelog');
    Route::get('release/{release}', [ReleaseController::class, 'view'])->name('release.view');

    Route::get('health', [SiteController::class, 'health'])->name('misc.health');
    Route::get('mapping', [SiteController::class, 'mapping'])->name('misc.mapping');

    Route::get('affixes', [SiteController::class, 'affixes'])->name('misc.affixes');

    Route::get('timetest', [SiteController::class, 'timetest'])->name('misc.timetest');

    Route::get('embed/{dungeonRoute}', [SiteController::class, 'embed'])->name('misc.embed');

    Route::get('status', [SiteController::class, 'status'])->name('misc.status');

    Route::get('login/google', [GoogleLoginController::class, 'redirectToProvider'])->name('login.google');
    Route::get('login/google/callback', [GoogleLoginController::class, 'handleProviderCallback'])->name('login.google.callback');

    Route::get('login/battlenet', [BattleNetLoginController::class, 'redirectToProvider'])->name('login.battlenet');
    Route::get('login/battlenet/callback', [BattleNetLoginController::class, 'handleProviderCallback'])->name('login.battlenet.callback');

    Route::get('login/discord', [DiscordLoginController::class, 'redirectToProvider'])->name('login.discord');
    Route::get('login/discord/callback', [DiscordLoginController::class, 'handleProviderCallback'])->name('login.discord.callback');

    Route::get('new', [DungeonRouteController::class, 'new'])->name('dungeonroute.new');
    Route::post('new', [DungeonRouteController::class, 'savenew'])->name('dungeonroute.savenew');

    Route::get('new/temporary', [DungeonRouteController::class, 'newtemporary'])->name('dungeonroute.temporary.new');
    Route::post('new/temporary', [DungeonRouteController::class, 'savenewtemporary'])->name('dungeonroute.temporary.savenew');

    Route::post('new/mdtimport', [MDTImportController::class, 'import'])->name('dungeonroute.new.mdtimport');

    Route::get('patreon-link', [PatreonController::class, 'link'])->name('patreon.link');
    Route::get('patreon-oauth', [PatreonController::class, 'oauth_redirect'])->name('patreon.oauth.redirect');

    Route::get('dungeonroutes', [SiteController::class, 'dungeonroutes']);
    Route::get('search', [DungeonRouteDiscoverController::class, 'search'])->name('dungeonroutes.search');

    // Game version toggle
    Route::group(['prefix' => 'gameversion'], function () {
        Route::get('/{gameVersion}', [GameVersionController::class, 'update'])->name('gameversion.update');
    });

    // Profile routes
    Route::group(['prefix' => 'routes'], function () {
        Route::get('/', [DungeonRouteDiscoverController::class, 'discover'])->name('dungeonroutes');
        Route::group(['prefix' => '{expansion}'], function () {
            Route::get('/', [DungeonRouteDiscoverController::class, 'discoverExpansion'])->name('dungeonroutes.expansion');
            Route::get('popular', [DungeonRouteDiscoverController::class, 'discoverpopular'])->name('dungeonroutes.popular');
            Route::get('affixes/current', [DungeonRouteDiscoverController::class, 'discoverthisweek'])->name('dungeonroutes.thisweek');
            Route::get('affixes/next', [DungeonRouteDiscoverController::class, 'discovernextweek'])->name('dungeonroutes.nextweek');
            Route::get('new', [DungeonRouteDiscoverController::class, 'discovernew'])->name('dungeonroutes.new');

            Route::group(['prefix' => 'season/{season}'], function () {
                Route::get('/', [DungeonRouteDiscoverController::class, 'discoverSeason'])->name('dungeonroutes.season');
                Route::get('popular', [DungeonRouteDiscoverController::class, 'discoverSeasonPopular'])->name('dungeonroutes.season.popular');
                Route::get('affixes/current', [DungeonRouteDiscoverController::class, 'discoverSeasonThisWeek'])->name('dungeonroutes.season.thisweek');
                Route::get('affixes/next', [DungeonRouteDiscoverController::class, 'discoverSeasonNextWeek'])->name('dungeonroutes.season.nextweek');
                Route::get('new', [DungeonRouteDiscoverController::class, 'discoverSeasonNew'])->name('dungeonroutes.season.new');
            });

            Route::group(['prefix' => '{dungeon}'], function () {
                Route::get('/', [DungeonRouteDiscoverController::class, 'discoverdungeon'])->name('dungeonroutes.discoverdungeon');
                Route::get('popular', [DungeonRouteDiscoverController::class, 'discoverdungeonpopular'])->name('dungeonroutes.discoverdungeon.popular');
                Route::get('affixes/current', [DungeonRouteDiscoverController::class, 'discoverdungeonthisweek'])->name('dungeonroutes.discoverdungeon.thisweek');
                Route::get('affixes/next', [DungeonRouteDiscoverController::class, 'discoverdungeonnextweek'])->name('dungeonroutes.discoverdungeon.nextweek');
                Route::get('new', [DungeonRouteDiscoverController::class, 'discoverdungeonnew'])->name('dungeonroutes.discoverdungeon.new');
            });
        });
    });

    // Explore dungeons (just show me the mapping but don't allow me to create routes)
    Route::group(['prefix' => 'explore'], function () {
        Route::get('/', [DungeonExploreController::class, 'list'])->name('dungeon.explore.list');

        Route::group(['prefix' => '{dungeon}'], function () {
            Route::get('/', [DungeonExploreController::class, 'viewDungeon'])->name('dungeon.explore.view');
        });
    });

    // May be accessed without being logged in
    Route::get('team/invite/{invitecode}', [TeamController::class, 'invite'])->name('team.invite');

    // May not be logged in - we have anonymous routes
    Route::group(['prefix' => '/route/{dungeon}/{dungeonroute}'], function () {
        Route::get('/', [DungeonRouteController::class, 'view'])->name('dungeonroute.editnotitle');

        Route::group(['prefix' => '{title?}'], function () {
            // Upgrade the mapping of a route
            Route::get('upgrade', [DungeonRouteController::class, 'upgrade'])->name('dungeonroute.upgrade');
            // Edit your own dungeon routes
            Route::get('edit', [DungeonRouteController::class, 'edit'])->name('dungeonroute.edit');
            Route::get('edit/{floorindex}', [DungeonRouteController::class, 'editfloor'])->name('dungeonroute.edit.floor');
            // Submit a patch for your own dungeon route
            Route::patch('edit', [DungeonRouteController::class, 'update'])->name('dungeonroute.update');

            Route::group(['middleware' => ['auth', 'role:user|admin']], function () {
                // Live sessions are only available for logged in users - for the synchronization stuff you MUST have a session
                Route::get('live', [LiveSessionController::class, 'create'])->name('dungeonroute.livesession.create');
                Route::get('live/{livesession}', [LiveSessionController::class, 'view'])->name('dungeonroute.livesession.view');
                Route::get('live/{livesession}/{floorIndex}', [LiveSessionController::class, 'viewfloor'])->name('dungeonroute.livesession.viewfloor');

                // Clone a route
                Route::get('clone', [DungeonRouteController::class, 'clone'])->name('dungeonroute.clone');
                // Claiming a route that was made by /sandbox functionality
                Route::get('claim', [DungeonRouteController::class, 'claim'])->name('dungeonroute.claim');

                Route::get('migrate/{seasonalType}', [DungeonRouteController::class, 'migrateToSeasonalType'])->name('dungeonroute.migrate');
            });
        });
    });


    Route::group(['prefix' => '{dungeonroute}'], function () {
        // Edit your own dungeon routes
        Route::get('edit', [DungeonRouteLegacyController::class, 'edit']);
        Route::get('edit/{floorindex}', [DungeonRouteLegacyController::class, 'editfloor']);

        Route::group(['middleware' => ['auth', 'role:user|admin']], function () {
            // Live sessions are only available for logged in users - for the synchronization stuff you MUST have a session
            Route::get('live/{livesession}', [LiveSessionLegacyController::class, 'view']);
            Route::get('live/{livesession}/{floorIndex}', [LiveSessionLegacyController::class, 'viewfloor']);

            // Clone a route
            Route::get('clone', [DungeonRouteLegacyController::class, 'cloneold']);
            // Claiming a route that was made by /sandbox functionality
            Route::get('claim', [DungeonRouteLegacyController::class, 'claimold']);
        });
    });

    Route::group(['middleware' => ['auth', 'role:user|admin']], function () {
        Route::get('patreon-unlink', [PatreonController::class, 'unlink'])->name('patreon.unlink');

        // Profile routes
        Route::group(['prefix' => 'profile'], function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::get('routes', [ProfileController::class, 'routes'])->name('profile.routes');
            Route::get('favorites', [ProfileController::class, 'favorites'])->name('profile.favorites');
            Route::get('tags', [ProfileController::class, 'tags'])->name('profile.tags');
            Route::patch('{user}', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('delete', [ProfileController::class, 'delete'])->name('profile.delete');
            Route::patch('{user}/privacy', [ProfileController::class, 'updatePrivacy'])->name('profile.updateprivacy');
            Route::patch('/', [ProfileController::class, 'changepassword'])->name('profile.changepassword');
            Route::post('tag', [ProfileController::class, 'createtag'])->name('profile.tag.create');
        });

        Route::get('teams', [TeamController::class, 'list'])->name('team.list');
        Route::group(['prefix' => 'team'], function () {
            Route::get('new', [TeamController::class, 'new'])->name('team.new');
            Route::get('{team}', [TeamController::class, 'edit'])->name('team.edit');
            Route::delete('{team}', [TeamController::class, 'delete'])->name('team.delete');
            Route::post('tag', [TeamController::class, 'createtag'])->name('team.tag.create');

            Route::post('new', [TeamController::class, 'savenew'])->name('team.savenew');
            Route::patch('{team}', [TeamController::class, 'update'])->name('team.update');
            Route::get('invite/{invitecode}/accept', [TeamController::class, 'inviteaccept'])->name('team.invite.accept');
        });
    });

    Route::group(['middleware' => ['auth', 'role:admin']], function () {
        // Only admins may view a list of profiles
        Route::get('profiles', [ProfileController::class, 'list'])->name('profile.list');

        Route::get('phpinfo', [SiteController::class, 'phpinfo'])->name('misc.phpinfo');

        Route::group(['prefix' => 'admin'], function () {
            // Dungeons
            Route::group(['prefix' => 'dungeon'], function () {
                Route::get('new', [DungeonController::class, 'new'])->name('admin.dungeon.new');
                Route::get('{dungeon}', [DungeonController::class, 'edit'])->name('admin.dungeon.edit');

                Route::post('new', [DungeonController::class, 'savenew'])->name('admin.dungeon.savenew');
                Route::patch('{dungeon}', [DungeonController::class, 'update'])->name('admin.dungeon.update');

                // Mapping versions
                Route::group(['prefix' => '{dungeon}/mappingversion'], function () {
                    Route::get('new', [MappingVersionController::class, 'savenew'])->name('admin.mappingversion.new');
                    Route::get('{mappingVersion}/delete', [MappingVersionController::class, 'delete'])->name('admin.mappingversion.delete');
                });

                // Floors
                Route::group(['prefix' => '{dungeon}/floor'], function () {
                    Route::get('new', [FloorController::class, 'new'])->name('admin.floor.new');

                    Route::post('new', [FloorController::class, 'savenew'])->name('admin.floor.savenew');

                    Route::group(['prefix' => '{floor}'], function () {
                        Route::get('/', [FloorController::class, 'edit'])->name('admin.floor.edit');
                        Route::patch('/', [FloorController::class, 'update'])->name('admin.floor.update');
                        Route::get('mapping', [FloorController::class, 'mapping'])->name('admin.floor.edit.mapping');

                        // Speedrun required npcs
                        Route::group(['prefix' => 'speedrunrequirednpcs'], function () {
                            Route::get('{difficulty}/new', [DungeonSpeedrunRequiredNpcsController::class, 'new'])->name('admin.dungeonspeedrunrequirednpc.new');
                            Route::post('{difficulty}/new', [DungeonSpeedrunRequiredNpcsController::class, 'savenew'])->name('admin.dungeonspeedrunrequirednpc.savenew');
                            Route::get('{difficulty}/{dungeonspeedrunrequirednpc}', [DungeonSpeedrunRequiredNpcsController::class, 'delete'])->name('admin.dungeonspeedrunrequirednpc.delete');
                        });
                    });
                });
            });
            Route::get('dungeons', [DungeonController::class, 'list'])->name('admin.dungeons');

            // Expansions
            Route::group(['prefix' => 'expansion'], function () {
                Route::get('new', [ExpansionController::class, 'new'])->name('admin.expansion.new');
                Route::get('{expansion}', [ExpansionController::class, 'edit'])->name('admin.expansion.edit');

                Route::post('new', [ExpansionController::class, 'savenew'])->name('admin.expansion.savenew');
                Route::patch('{expansion}', [ExpansionController::class, 'update'])->name('admin.expansion.update');
            });
            Route::get('expansions', [ExpansionController::class, 'list'])->name('admin.expansions');

            // Releases
            Route::group(['prefix' => 'release'], function () {
                Route::get('new', [ReleaseController::class, 'new'])->name('admin.release.new');
                Route::get('{release}', [ReleaseController::class, 'edit'])->name('admin.release.edit');

                Route::post('new', [ReleaseController::class, 'savenew'])->name('admin.release.savenew');
                Route::patch('{release}', [ReleaseController::class, 'update'])->name('admin.release.update');

                Route::get('/', [ReleaseController::class, 'list'])->name('admin.releases');
            });

            // NPCs
            Route::group(['prefix' => 'npc'], function () {
                Route::get('new', [NpcController::class, 'new'])->name('admin.npc.new');

                Route::post('new', [NpcController::class, 'savenew'])->name('admin.npc.savenew');

                Route::group(['prefix' => '{npc}'], function () {
                    Route::get('/', [NpcController::class, 'edit'])->name('admin.npc.edit');
                    Route::patch('/', [NpcController::class, 'update'])->name('admin.npc.update');

                    Route::group(['prefix' => 'npcEnemyForces/{npcEnemyForces}'], function () {
                        Route::get('/', [NpcEnemyForcesController::class, 'edit'])->name('admin.npcenemyforces.edit');
                        Route::patch('/', [NpcEnemyForcesController::class, 'update'])->name('admin.npcenemyforces.update');
                    });
                });

            });
            Route::get('npcs', [NpcController::class, 'list'])->name('admin.npcs');

            // Spells
            Route::group(['prefix' => 'spell'], function () {
                Route::get('new', [SpellController::class, 'new'])->name('admin.spell.new');
                Route::get('{spell}', [SpellController::class, 'edit'])->name('admin.spell.edit');

                Route::post('new', [SpellController::class, 'savenew'])->name('admin.spell.savenew');
                Route::patch('{spell}', [SpellController::class, 'update'])->name('admin.spell.update');
            });
            Route::get('spells', [SpellController::class, 'list'])->name('admin.spells');

            Route::group(['prefix' => 'user'], function () {
                Route::post('{user}/makeadmin', [UserController::class, 'makeadmin'])->name('admin.user.makeadmin');
                Route::post('{user}/makeuser', [UserController::class, 'makeuser'])->name('admin.user.makeuser');
                Route::delete('{user}/delete', [UserController::class, 'delete'])->name('admin.user.delete');
                Route::get('{user}/grantAllBenefits', [UserController::class, 'grantAllBenefits'])->name('admin.user.grantallbenefits');
            });
            Route::get('users', [UserController::class, 'list'])->name('admin.users');

            Route::get('userreports', [UserReportController::class, 'list'])->name('admin.userreports');

            Route::group(['prefix' => 'tools'], function () {
                Route::get('/', [AdminToolsController::class, 'index'])->name('admin.tools');

                Route::get('/combatlog', [AdminToolsController::class, 'combatlog'])->name('admin.combatlog');

                Route::get('/npcimport', [AdminToolsController::class, 'npcimport'])->name('admin.tools.npcimport');
                Route::post('/npcimport', [AdminToolsController::class, 'npcimportsubmit'])->name('admin.tools.npcimport.submit');

                // Dungeonroute
                Route::get('/dungeonroute', [AdminToolsController::class, 'dungeonroute'])->name('admin.tools.dungeonroute.view');
                Route::post('/dungeonroute', [AdminToolsController::class, 'dungeonroutesubmit'])->name('admin.tools.dungeonroute.view.submit');

                Route::get('/dungeonroute/mappingversions', [AdminToolsController::class, 'dungeonrouteMappingVersions'])->name('admin.tools.dungeonroute.mappingversionusage');

                // Import enemy forces
                Route::get('enemyforces/import', [AdminToolsController::class, 'enemyforcesimport'])->name('admin.tools.enemyforces.import.view');
                Route::post('enemyforces/import', [AdminToolsController::class, 'enemyforcesimportsubmit'])->name('admin.tools.enemyforces.import.submit');

                Route::get('enemyforces/recalculate', [AdminToolsController::class, 'enemyforcesrecalculate'])->name('admin.tools.enemyforces.recalculate.view');
                Route::post('enemyforces/recalculate', [AdminToolsController::class, 'enemyforcesrecalculatesubmit'])->name('admin.tools.enemyforces.recalculate.submit');

                // Thumbnails
                Route::get('thumbnails/regenerate', [AdminToolsController::class, 'thumbnailsregenerate'])->name('admin.tools.thumbnails.regenerate.view');
                Route::post('thumbnails/regenerate', [AdminToolsController::class, 'thumbnailsregeneratesubmit'])->name('admin.tools.thumbnails.regenerate.submit');

                Route::group(['prefix' => 'mdt'], function () {
                    // View string contents
                    Route::get('string', [AdminToolsController::class, 'mdtview'])->name('admin.tools.mdt.string.view');
                    Route::post('string', [AdminToolsController::class, 'mdtviewsubmit'])->name('admin.tools.mdt.string.submit');

                    // View string contents as a dungeonroute
                    Route::get('string/dungeonroute', [AdminToolsController::class, 'mdtviewasdungeonroute'])->name('admin.tools.mdt.string.viewasdungeonroute');
                    Route::post('string/dungeonroute', [AdminToolsController::class, 'mdtviewasdungeonroutesubmit'])->name('admin.tools.mdt.string.viewasdungeonroute.submit');

                    // View dungeonroute as string
                    Route::get('dungeonroute/string', [AdminToolsController::class, 'mdtviewasstring'])->name('admin.tools.mdt.dungeonroute.viewasstring');
                    Route::post('dungeonroute/string', [AdminToolsController::class, 'mdtviewasstringsubmit'])->name('admin.tools.mdt.dungeonroute.viewasstring.submit');

                    // View mapping hash
                    Route::get('dungeonmappinghash', [AdminToolsController::class, 'mdtdungeonmappinghash'])->name('admin.tools.mdt.dungeonmappinghash');
                    Route::post('dungeonmappinghash', [AdminToolsController::class, 'mdtdungeonmappinghashsubmit'])->name('admin.tools.mdt.dungeonmappinghash.submit');

                    // Convert Mapping Version to MDT Mapping
                    Route::get('dungeonmappingversiontomdtmapping', [AdminToolsController::class, 'dungeonmappingversiontomdtmapping'])->name('admin.tools.mdt.dungeonmappingversiontomdtmapping');
                    Route::post('dungeonmappingversiontomdtmapping', [AdminToolsController::class, 'dungeonmappingversiontomdtmappingsubmit'])->name('admin.tools.mdt.dungeonmappingversiontomdtmapping.submit');

                });

                // Wow.tools
                Route::get('wowtools/importingamecoordinates', [AdminToolsController::class, 'importingamecoordinates'])->name('admin.tools.wowtools.import_ingame_coordinates');
                Route::post('wowtools/importingamecoordinates', [AdminToolsController::class, 'importingamecoordinatessubmit'])->name('admin.tools.wowtools.import_ingame_coordinates.submit');

                // Exception thrower
                Route::get('exception', [AdminToolsController::class, 'exceptionselect'])->name('admin.tools.exception.select');
                Route::post('exception', [AdminToolsController::class, 'exceptionselectsubmit'])->name('admin.tools.exception.select.submit');

                Route::get('mdt/diff', [AdminToolsController::class, 'mdtdiff'])->name('admin.tools.mdt.diff');
                Route::get('cache/drop', [AdminToolsController::class, 'dropcache'])->name('admin.tools.cache.drop');
                Route::get('mapping/forcesync', [AdminToolsController::class, 'mappingForceSync'])->name('admin.tools.mapping.forcesync');


                Route::get('datadump/exportdungeondata', [AdminToolsController::class, 'exportdungeondata'])->name('admin.tools.datadump.exportdungeondata');
                Route::get('datadump/exportreleases', [AdminToolsController::class, 'exportreleases'])->name('admin.tools.datadump.exportreleases');
            });
        });
    });

    Route::group(['prefix' => 'ajax', 'middleware' => 'ajax'], function () {
        Route::get('refresh-csrf', [AjaxSiteController::class, 'refreshCsrf'])->name('api.refresh_csrf');

        Route::group(['prefix' => 'tag'], function () {
            Route::get('/', [AjaxTagController::class, 'all'])->name('api.tag.all');
            Route::get('/{category}', [AjaxTagController::class, 'list'])->name('api.tag.list');
            Route::post('/', [AjaxTagController::class, 'store'])->name('api.tag.create');
            Route::delete('/{tag}', [AjaxTagController::class, 'delete'])->name('api.tag.delete');
            // Profile
            Route::put('/{tag}/all', [AjaxTagController::class, 'updateAll'])->name('api.tag.updateall');
            Route::delete('/{tag}/all', [AjaxTagController::class, 'deleteAll'])->name('api.tag.deleteall');
        });

        Route::get('/{publickey}/data', [AjaxDungeonRouteController::class, 'data']);

        Route::post('userreport/dungeonroute/{dungeonroute}', [AjaxUserReportController::class, 'dungeonrouteStore'])->name('userreport.dungeonroute');
        Route::post('userreport/enemy/{enemy}', [AjaxUserReportController::class, 'enemyStore'])->name('userreport.enemy');

        Route::get('/routes', [AjaxDungeonRouteController::class, 'list']);
        Route::get('/search', [AjaxDungeonRouteController::class, 'htmlsearch']);
        Route::get('/search/{category}', [AjaxDungeonRouteController::class, 'htmlsearchcategory']);

        Route::post('/mdt/details', [MDTImportController::class, 'details'])->name('mdt.details');

        Route::post('/profile/legal', [AjaxProfileController::class, 'legalAgree']);

        // Metrics
        Route::group(['prefix' => 'metric'], function () {
            Route::post('/', [AjaxMetricController::class, 'store'])->name('api.metric.store');
            Route::post('/route/{dungeonRoute}', [AjaxMetricController::class, 'storeDungeonRoute'])->name('api.metric.store.dungeonroute');
        });

        // Must be an admin to perform these actions
        Route::group(['middleware' => ['auth', 'role:admin']], function () {
            Route::group(['prefix' => 'admin'], function () {
                Route::patch('mappingVersion/{mappingVersion}', [AjaxMappingVersionController::class, 'store']);

                Route::get('/user', [AjaxUserController::class, 'list']);
                Route::get('/npc', [AjaxNpcController::class, 'list']);

                Route::post('/enemy', [AjaxEnemyController::class, 'store']);
                Route::put('/enemy/{enemy}', [AjaxEnemyController::class, 'store']);
                Route::delete('/enemy/{enemy}', [AjaxEnemyController::class, 'delete']);

                Route::post('/enemypack', [AjaxEnemyPackController::class, 'store']);
                Route::put('/enemypack/{enemyPack}', [AjaxEnemyPackController::class, 'store']);
                Route::delete('/enemypack/{enemyPack}', [AjaxEnemyPackController::class, 'delete']);

                Route::post('/enemypatrol', [AjaxEnemyPatrolController::class, 'store']);
                Route::put('/enemypatrol/{enemyPatrol}', [AjaxEnemyPatrolController::class, 'store']);
                Route::delete('/enemypatrol/{enemyPatrol}', [AjaxEnemyPatrolController::class, 'delete']);

                Route::post('/dungeonfloorswitchmarker', [AjaxDungeonFloorSwitchMarkerController::class, 'store']);
                Route::put('/dungeonfloorswitchmarker/{dungeonFloorSwitchMarker}', [AjaxDungeonFloorSwitchMarkerController::class, 'store']);
                Route::delete('/dungeonfloorswitchmarker/{dungeonFloorSwitchMarker}', [AjaxDungeonFloorSwitchMarkerController::class, 'delete']);

                Route::post('/mapicon', [AjaxMapIconController::class, 'adminStore']);
                Route::put('/mapicon/{mapIcon}', [AjaxMapIconController::class, 'adminStore']);
                Route::delete('/mapicon/{mapIcon}', [AjaxMapIconController::class, 'adminDelete']);

                Route::post('/mountablearea', [AjaxMountableAreaController::class, 'store']);
                Route::put('/mountablearea/{mountableArea}', [AjaxMountableAreaController::class, 'store']);
                Route::delete('/mountablearea/{mountableArea}', [AjaxMountableAreaController::class, 'delete']);

                Route::post('/thumbnail/{dungeonroute}/refresh', [AjaxDungeonRouteController::class, 'refreshThumbnail']);
            });

            Route::put('/userreport/{userreport}/status', [AjaxUserReportController::class, 'status']);

            Route::post('/tools/mdt/diff/apply', [AdminToolsController::class, 'applychange']);

            Route::put('/user/{user}/patreon/benefits', [UserController::class, 'storePatreonBenefits']);
        });

        Route::group(['prefix' => 'dungeonRoute'], function () {
            Route::post('/data', [AjaxDungeonRouteController::class, 'getDungeonRoutesData']);
        });

        // May be performed without being logged in (sandbox functionality)
        Route::group(['prefix' => '{dungeonRoute}'], function () {


            Route::post('/brushline', [AjaxBrushlineController::class, 'store']);
            Route::put('/brushline/{brushline}', [AjaxBrushlineController::class, 'store']);
            Route::delete('/brushline/{brushline}', [AjaxBrushlineController::class, 'delete']);

            Route::put('/killzone/mass', [AjaxKillZoneController::class, 'storeAll']);
            Route::post('/killzone', [AjaxKillZoneController::class, 'store']);
            Route::put('/killzone/{killZone}', [AjaxKillZoneController::class, 'store']);
            Route::delete('/killzone/{killZone}', [AjaxKillZoneController::class, 'delete']);
            Route::delete('/killzone', [AjaxKillZoneController::class, 'deleteAll']);

            Route::post('/mapicon', [AjaxMapIconController::class, 'store']);
            Route::put('/mapicon/{mapIcon}', [AjaxMapIconController::class, 'store']);
            Route::delete('/mapicon/{mapIcon}', [AjaxMapIconController::class, 'delete']);

            Route::post('/pridefulenemy/{enemy}', [AjaxPridefulEnemyController::class, 'store']);
            Route::delete('/pridefulenemy/{enemy}', [AjaxPridefulEnemyController::class, 'delete']);

            Route::post('/path', [AjaxPathController::class, 'store']);
            Route::put('/path/{path}', [AjaxPathController::class, 'store']);
            Route::delete('/path/{path}', [AjaxPathController::class, 'delete']);

            Route::post('/raidmarker/{enemy}', [AjaxEnemyController::class, 'setRaidMarker']);

            Route::post('/clone/team/{team}', [AjaxDungeonRouteController::class, 'cloneToTeam']);

            Route::get('/mdtExport', [AjaxDungeonRouteController::class, 'mdtExport'])->name('api.dungeonroute.mdtexport');
            Route::post('/simulate', [AjaxDungeonRouteController::class, 'simulate'])->name('api.dungeonroute.simulate');
        });

        // Must be logged in to perform these actions
        Route::group(['middleware' => ['auth', 'role:user|admin']], function () {
            Route::group(['prefix' => '{dungeonRoute}'], function () {
                Route::patch('/', [AjaxDungeonRouteController::class, 'store'])->name('api.dungeonroute.update');
                Route::patch('/pullgradient', [AjaxDungeonRouteController::class, 'storePullGradient'])->name('api.dungeonroute.pullgradient.update');
                Route::delete('/', [AjaxDungeonRouteController::class, 'delete'])->name('api.dungeonroute.delete');

                Route::post('/favorite', [AjaxDungeonRouteController::class, 'favorite'])->name('api.dungeonroute.favorite');
                Route::delete('/favorite', [AjaxDungeonRouteController::class, 'favoriteDelete'])->name('api.dungeonroute.favorite.delete');

                Route::post('/publishedState', [AjaxDungeonRouteController::class, 'publishedState'])->name('api.dungeonroute.publishedstate');

                Route::post('/rate', [AjaxDungeonRouteController::class, 'rate'])->name('api.dungeonroute.rate');
                Route::delete('/rate', [AjaxDungeonRouteController::class, 'rateDelete'])->name('api.dungeonroute.rate.delete');

                Route::post('/migrate/{seasonalType}', [AjaxDungeonRouteController::class, 'migrateToSeasonalType']);

                Route::group(['prefix' => '/live/{liveSession}'], function () {
                    Route::delete('/', [AjaxLiveSessionController::class, 'delete']);

                    Route::post('/overpulledenemy', [AjaxOverpulledEnemyController::class, 'store']);
                    Route::delete('/overpulledenemy', [AjaxOverpulledEnemyController::class, 'delete']);
                });
            });

            Route::group(['prefix' => 'echo'], function () {
                // Echo controller misc
                Route::get('{dungeonRoute}/members', [AjaxEchoController::class, 'members']);
            });

            // Teams
            Route::group(['prefix' => 'team/{team}'], function () {
                Route::put('/changedefaultrole', [AjaxTeamController::class, 'changeDefaultRole']);
                Route::put('/changerole', [AjaxTeamController::class, 'changeRole']);
                Route::post('/route/{dungeonroute}', [AjaxTeamController::class, 'addRoute']);
                Route::delete('/member/{user}', [AjaxTeamController::class, 'removeMember']);
                Route::delete('/route/{dungeonroute}', [AjaxTeamController::class, 'removeRoute']);
                Route::get('/refreshlink', [AjaxTeamController::class, 'refreshInviteLink']);

                // Ad-free giveaway
                Route::post('/member/{user}/adfree', [AjaxTeamController::class, 'addAdFreeGiveaway']);
                Route::delete('/member/{user}/adfree', [AjaxTeamController::class, 'removeAdFreeGiveaway']);
            });
        });
    });

    // At the bottom to let routes such as profile/routes pass through first
    Route::get('profile/{user}', [ProfileController::class, 'view'])->name('profile.view');

    // View any dungeon route (catch all)

    Route::group(['prefix' => '/route/{dungeon}/{dungeonroute}'], function () {
        Route::get('/', [DungeonRouteController::class, 'view'])->name('dungeonroute.viewnotitle');

        Route::group(['prefix' => '{title?}'], function () {
            Route::get('/', [DungeonRouteController::class, 'view'])->name('dungeonroute.view');
            Route::get('present/', [DungeonRouteController::class, 'present'])->name('dungeonroute.present');
            Route::get('present/{floorindex}', [DungeonRouteController::class, 'presentFloor'])->name('dungeonroute.present.floor');

            Route::get('embed/', [DungeonRouteController::class, 'embed'])->name('dungeonroute.embed');
            Route::get('embed/{floorindex}', [DungeonRouteController::class, 'embed'])->name('dungeonroute.embed.floor');

            Route::get('{floorindex}', [DungeonRouteController::class, 'viewfloor'])->name('dungeonroute.view.floor');
            // Preview of a route for image capturing library
            Route::get('preview/{floorindex}', [DungeonRouteController::class, 'preview'])->name('dungeonroute.preview');
        });
    });

    Route::group(['prefix' => '{dungeonroute}'], function () {
        Route::get('/', [DungeonRouteLegacyController::class, 'viewold'])->name('dungeonroute.viewold');
        Route::get('embed/', [DungeonRouteLegacyController::class, 'embedold']);
        Route::get('embed/{floorindex}', [DungeonRouteLegacyController::class, 'embedold']);
        Route::get('{floorindex}', [DungeonRouteLegacyController::class, 'viewfloorold']);
        // Preview of a route for image capturing library
        Route::get('preview/{floorindex}', [DungeonRouteLegacyController::class, 'previewold']);
    });
});

Auth::routes();
