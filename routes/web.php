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
use App\Http\Controllers\Ajax\AjaxPridefulEnemyController;
use App\Http\Controllers\Ajax\AjaxProfileController;
use App\Http\Controllers\Ajax\AjaxSiteController;
use App\Http\Controllers\Ajax\AjaxSpellController;
use App\Http\Controllers\Ajax\AjaxTagController;
use App\Http\Controllers\Ajax\AjaxTeamController;
use App\Http\Controllers\Ajax\AjaxUserController;
use App\Http\Controllers\Ajax\AjaxUserReportController;
use App\Http\Controllers\Ajax\Floor\AjaxFloorUnionAreaController;
use App\Http\Controllers\Ajax\Floor\AjaxFloorUnionController;
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
use App\Http\Controllers\Floor\FloorController;
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
Route::post('webhook/github', (new WebhookController())->github(...))->name('webhook.github');

Route::middleware(['viewcachebuster', 'language', 'debugbarmessagelogger', 'read_only_mode', 'debug_info_context_logger'])->group(static function () {
    // Catch for hard-coded /home route in RedirectsUsers.php
    Route::get('home', (new SiteController())->home(...));
    Route::get('credits', (new SiteController())->credits(...))->name('misc.credits');
    Route::get('about', (new SiteController())->about(...))->name('misc.about');
    Route::get('privacy', (new SiteController())->privacy(...))->name('legal.privacy');
    Route::get('terms', (new SiteController())->terms(...))->name('legal.terms');
    Route::get('cookies', (new SiteController())->cookies(...))->name('legal.cookies');
    Route::get('/', (new SiteController())->index(...))->name('home');
    Route::get('changelog', (new SiteController())->changelog(...))->name('misc.changelog');
    Route::get('release/{release}', (new ReleaseController())->view(...))->name('release.view');
    Route::get('health', (new SiteController())->health(...))->name('misc.health');
    Route::get('mapping', (new SiteController())->mapping(...))->name('misc.mapping');
    Route::get('affixes', (new SiteController())->affixes(...))->name('misc.affixes');
    Route::get('timetest', (new SiteController())->timetest(...))->name('misc.timetest');
    Route::get('embed/{dungeonRoute}', (new SiteController())->embed(...))->name('misc.embed');
    Route::get('status', (new SiteController())->status(...))->name('misc.status');
    Route::get('login/google', (new GoogleLoginController())->redirectToProvider(...))->name('login.google');
    Route::get('login/google/callback', (new GoogleLoginController())->handleProviderCallback(...))->name('login.google.callback');
    Route::get('login/battlenet', (new BattleNetLoginController())->redirectToProvider(...))->name('login.battlenet');
    Route::get('login/battlenet/callback', (new BattleNetLoginController())->handleProviderCallback(...))->name('login.battlenet.callback');
    Route::get('login/discord', (new DiscordLoginController())->redirectToProvider(...))->name('login.discord');
    Route::get('login/discord/callback', (new DiscordLoginController())->handleProviderCallback(...))->name('login.discord.callback');
    Route::get('new', (new DungeonRouteController())->create(...))->name('dungeonroute.new');
    Route::post('new', (new DungeonRouteController())->saveNew(...))->name('dungeonroute.savenew');
    Route::get('new/temporary', (new DungeonRouteController())->createTemporary(...))->name('dungeonroute.temporary.new');
    Route::post('new/temporary', (new DungeonRouteController())->saveNewTemporary(...))->name('dungeonroute.temporary.savenew');
    Route::post('new/mdtimport', (new MDTImportController())->import(...))->name('dungeonroute.new.mdtimport');
    Route::get('patreon-link', (new PatreonController())->link(...))->name('patreon.link');
    Route::get('patreon-oauth', (new PatreonController())->oauth_redirect(...))->name('patreon.oauth.redirect');
    Route::get('dungeonroutes', (new SiteController())->dungeonroutes(...));
    Route::get('search', (new DungeonRouteDiscoverController())->search(...))->name('dungeonroutes.search');
    // Game version toggle
    Route::prefix('gameversion')->group(static function () {
        Route::get('/{gameVersion}', (new GameVersionController())->update(...))->name('gameversion.update');
    });
    // Profile routes
    Route::prefix('routes')->group(static function () {
        Route::get('/', (new DungeonRouteDiscoverController())->discover(...))->name('dungeonroutes');
        Route::prefix('{expansion}')->group(static function () {
            Route::get('/', (new DungeonRouteDiscoverController())->discoverExpansion(...))->name('dungeonroutes.expansion');
            Route::get('popular', (new DungeonRouteDiscoverController())->discoverpopular(...))->name('dungeonroutes.popular');
            Route::get('affixes/current', (new DungeonRouteDiscoverController())->discoverthisweek(...))->name('dungeonroutes.thisweek');
            Route::get('affixes/next', (new DungeonRouteDiscoverController())->discovernextweek(...))->name('dungeonroutes.nextweek');
            Route::get('new', (new DungeonRouteDiscoverController())->discovernew(...))->name('dungeonroutes.new');
            Route::prefix('season/{season}')->group(static function () {
                Route::get('/', (new DungeonRouteDiscoverController())->discoverSeason(...))->name('dungeonroutes.season');
                Route::get('popular', (new DungeonRouteDiscoverController())->discoverSeasonPopular(...))->name('dungeonroutes.season.popular');
                Route::get('affixes/current', (new DungeonRouteDiscoverController())->discoverSeasonThisWeek(...))->name('dungeonroutes.season.thisweek');
                Route::get('affixes/next', (new DungeonRouteDiscoverController())->discoverSeasonNextWeek(...))->name('dungeonroutes.season.nextweek');
                Route::get('new', (new DungeonRouteDiscoverController())->discoverSeasonNew(...))->name('dungeonroutes.season.new');
            });
            Route::prefix('{dungeon}')->group(static function () {
                Route::get('/', (new DungeonRouteDiscoverController())->discoverdungeon(...))->name('dungeonroutes.discoverdungeon');
                Route::get('popular', (new DungeonRouteDiscoverController())->discoverdungeonpopular(...))->name('dungeonroutes.discoverdungeon.popular');
                Route::get('affixes/current', (new DungeonRouteDiscoverController())->discoverdungeonthisweek(...))->name('dungeonroutes.discoverdungeon.thisweek');
                Route::get('affixes/next', (new DungeonRouteDiscoverController())->discoverdungeonnextweek(...))->name('dungeonroutes.discoverdungeon.nextweek');
                Route::get('new', (new DungeonRouteDiscoverController())->discoverdungeonnew(...))->name('dungeonroutes.discoverdungeon.new');
            });
        });
    });
    // Explore dungeons (just show me the mapping but don't allow me to create routes)
    Route::prefix('explore')->group(static function () {
        Route::get('/', (new DungeonExploreController())->get(...))->name('dungeon.explore.list');
        Route::prefix('{dungeon}')->group(static function () {
            Route::get('/', (new DungeonExploreController())->viewDungeon(...))->name('dungeon.explore.view');
            Route::get('/{floorIndex}', (new DungeonExploreController())->viewDungeonFloor(...))->name('dungeon.explore.view.floor');
        });
    });
    // May be accessed without being logged in
    Route::get('team/invite/{invitecode}', (new TeamController())->invite(...))->name('team.invite');
    // May not be logged in - we have anonymous routes
    Route::prefix('/route/{dungeon}/{dungeonroute}')->group(static function () {
        Route::get('/', (new DungeonRouteController())->view(...))->name('dungeonroute.editnotitle');
        Route::prefix('{title?}')->group(static function () {
            // Upgrade the mapping of a route
            Route::get('upgrade', (new DungeonRouteController())->upgrade(...))->name('dungeonroute.upgrade');
            // Edit your own dungeon routes
            Route::get('edit', (new DungeonRouteController())->edit(...))->name('dungeonroute.edit');
            Route::get('edit/{floorindex}', (new DungeonRouteController())->editFloor(...))->name('dungeonroute.edit.floor');
            // Submit a patch for your own dungeon route
            Route::patch('edit', (new DungeonRouteController())->update(...))->name('dungeonroute.update');
            Route::middleware(['auth', 'role:user|admin'])->group(static function () {
                // Live sessions are only available for logged in users - for the synchronization stuff you MUST have a session
                Route::get('live', (new LiveSessionController())->create(...))->name('dungeonroute.livesession.create');
                Route::get('live/{livesession}', (new LiveSessionController())->view(...))->name('dungeonroute.livesession.view');
                Route::get('live/{livesession}/{floorIndex}', (new LiveSessionController())->viewfloor(...))->name('dungeonroute.livesession.viewfloor');
                // Clone a route
                Route::get('clone', (new DungeonRouteController())->copy(...))->name('dungeonroute.clone');
                // Claiming a route that was made by /sandbox functionality
                Route::get('claim', (new DungeonRouteController())->claim(...))->name('dungeonroute.claim');
                Route::get('migrate/{seasonalType}', (new DungeonRouteController())->migrateToSeasonalType(...))->name('dungeonroute.migrate');
            });
        });
    });
    Route::prefix('{dungeonroute}')->group(static function () {
        // Edit your own dungeon routes
        Route::get('edit', (new DungeonRouteLegacyController())->edit(...));
        Route::get('edit/{floorindex}', (new DungeonRouteLegacyController())->editfloor(...));
        Route::middleware(['auth', 'role:user|admin'])->group(static function () {
            // Live sessions are only available for logged in users - for the synchronization stuff you MUST have a session
            Route::get('live/{livesession}', (new LiveSessionLegacyController())->view(...));
            Route::get('live/{livesession}/{floorIndex}', (new LiveSessionLegacyController())->viewfloor(...));
            // Clone a route
            Route::get('clone', (new DungeonRouteLegacyController())->cloneold(...));
            // Claiming a route that was made by /sandbox functionality
            Route::get('claim', (new DungeonRouteLegacyController())->claimold(...));
        });
    });
    Route::middleware(['auth', 'role:user|admin'])->group(static function () {
        Route::get('patreon-unlink', (new PatreonController())->unlink(...))->name('patreon.unlink');
        // Profile routes
        Route::prefix('profile')->group(static function () {
            Route::get('/', (new ProfileController())->edit(...))->name('profile.edit');
            Route::get('routes', (new ProfileController())->routes(...))->name('profile.routes');
            Route::get('favorites', (new ProfileController())->favorites(...))->name('profile.favorites');
            Route::get('tags', (new ProfileController())->tags(...))->name('profile.tags');
            Route::patch('{user}', (new ProfileController())->update(...))->name('profile.update');
            Route::delete('delete', (new ProfileController())->delete(...))->name('profile.delete');
            Route::patch('{user}/privacy', (new ProfileController())->updatePrivacy(...))->name('profile.updateprivacy');
            Route::patch('/', (new ProfileController())->changepassword(...))->name('profile.changepassword');
            Route::post('tag', (new ProfileController())->createtag(...))->name('profile.tag.create');
        });
        Route::get('teams', (new TeamController())->get(...))->name('team.list');
        Route::prefix('team')->group(static function () {
            Route::get('new', (new TeamController())->create(...))->name('team.new');
            Route::get('{team}', (new TeamController())->edit(...))->name('team.edit');
            Route::delete('{team}', (new TeamController())->delete(...))->name('team.delete');
            Route::post('tag', (new TeamController())->createtag(...))->name('team.tag.create');
            Route::post('new', (new TeamController())->savenew(...))->name('team.savenew');
            Route::patch('{team}', (new TeamController())->update(...))->name('team.update');
            Route::get('invite/{invitecode}/accept', (new TeamController())->inviteaccept(...))->name('team.invite.accept');
        });
    });
    Route::middleware(['auth', 'role:admin'])->group(static function () {
        // Only admins may view a list of profiles
        Route::get('profiles', (new ProfileController())->get(...))->name('profile.list');
        Route::get('phpinfo', (new SiteController())->phpinfo(...))->name('misc.phpinfo');
        Route::prefix('admin')->group(static function () {
            // Dungeons
            Route::prefix('dungeon')->group(static function () {
                Route::get('new', (new DungeonController())->create(...))->name('admin.dungeon.new');
                Route::get('{dungeon}', (new DungeonController())->edit(...))->name('admin.dungeon.edit');
                Route::post('new', (new DungeonController())->savenew(...))->name('admin.dungeon.savenew');
                Route::patch('{dungeon}', (new DungeonController())->update(...))->name('admin.dungeon.update');
                // Mapping versions
                Route::prefix('{dungeon}/mappingversion')->group(static function () {
                    Route::get('new', (new MappingVersionController())->saveNew(...))->name('admin.mappingversion.new');
                    Route::get('new/bare', (new MappingVersionController())->saveNewBare(...))->name('admin.mappingversion.newbare');
                    Route::get('{mappingVersion}/delete', (new MappingVersionController())->delete(...))->name('admin.mappingversion.delete');
                });
                // Floors
                Route::prefix('{dungeon}/floor')->group(static function () {
                    Route::get('new', (new FloorController())->create(...))->name('admin.floor.new');
                    Route::post('new', (new FloorController())->savenew(...))->name('admin.floor.savenew');
                    Route::prefix('{floor}')->group(static function () {
                        Route::get('/', (new FloorController())->edit(...))->name('admin.floor.edit');
                        Route::patch('/', (new FloorController())->update(...))->name('admin.floor.update');
                        Route::get('mapping', (new FloorController())->mapping(...))->name('admin.floor.edit.mapping');
                        // Speedrun required npcs
                        Route::prefix('speedrunrequirednpcs')->group(static function () {
                            Route::get('{difficulty}/new', (new DungeonSpeedrunRequiredNpcsController())->create(...))->name('admin.dungeonspeedrunrequirednpc.new');
                            Route::post('{difficulty}/new', (new DungeonSpeedrunRequiredNpcsController())->createSave(...))->name('admin.dungeonspeedrunrequirednpc.savenew');
                            Route::get('{difficulty}/{dungeonspeedrunrequirednpc}', (new DungeonSpeedrunRequiredNpcsController())->delete(...))->name('admin.dungeonspeedrunrequirednpc.delete');
                        });
                    });
                });
            });
            Route::get('dungeons', (new DungeonController())->get(...))->name('admin.dungeons');
            // Expansions
            Route::prefix('expansion')->group(static function () {
                Route::get('new', (new ExpansionController())->create(...))->name('admin.expansion.new');
                Route::get('{expansion}', (new ExpansionController())->edit(...))->name('admin.expansion.edit');
                Route::post('new', (new ExpansionController())->savenew(...))->name('admin.expansion.savenew');
                Route::patch('{expansion}', (new ExpansionController())->update(...))->name('admin.expansion.update');
            });
            Route::get('expansions', (new ExpansionController())->get(...))->name('admin.expansions');
            // Releases
            Route::prefix('release')->group(static function () {
                Route::get('new', (new ReleaseController())->create(...))->name('admin.release.new');
                Route::get('{release}', (new ReleaseController())->edit(...))->name('admin.release.edit');
                Route::post('new', (new ReleaseController())->savenew(...))->name('admin.release.savenew');
                Route::patch('{release}', (new ReleaseController())->update(...))->name('admin.release.update');
                Route::get('/', (new ReleaseController())->get(...))->name('admin.releases');
            });
            // NPCs
            Route::prefix('npc')->group(static function () {
                Route::get('new', (new NpcController())->create(...))->name('admin.npc.new');
                Route::post('new', (new NpcController())->savenew(...))->name('admin.npc.savenew');
                Route::prefix('{npc}')->group(static function () {
                    Route::get('/', (new NpcController())->edit(...))->name('admin.npc.edit');
                    Route::patch('/', (new NpcController())->update(...))->name('admin.npc.update');
                    Route::prefix('npcEnemyForces/{npcEnemyForces}')->group(static function () {
                        Route::get('/', (new NpcEnemyForcesController())->edit(...))->name('admin.npcenemyforces.edit');
                        Route::patch('/', (new NpcEnemyForcesController())->update(...))->name('admin.npcenemyforces.update');
                    });
                });
            });
            Route::get('npcs', (new NpcController())->get(...))->name('admin.npcs');
            // Spells
            Route::prefix('spell')->group(static function () {
                Route::get('new', (new SpellController())->create(...))->name('admin.spell.new');
                Route::get('{spell}', (new SpellController())->edit(...))->name('admin.spell.edit');
                Route::post('new', (new SpellController())->savenew(...))->name('admin.spell.savenew');
                Route::patch('{spell}', (new SpellController())->update(...))->name('admin.spell.update');
            });
            Route::get('spells', (new SpellController())->get(...))->name('admin.spells');
            Route::prefix('user')->group(static function () {
                Route::post('{user}/make/{role}', (new UserController())->makeRole(...))->name('admin.user.make.role');
                Route::delete('{user}/delete', (new UserController())->delete(...))->name('admin.user.delete');
                Route::get('{user}/grantAllBenefits', (new UserController())->grantAllBenefits(...))->name('admin.user.grantallbenefits');
            });
            Route::get('users', (new UserController())->get(...))->name('admin.users');
            Route::get('userreports', (new UserReportController())->get(...))->name('admin.userreports');
            Route::prefix('tools')->group(static function () {
                Route::get('/', (new AdminToolsController())->index(...))->name('admin.tools');
                Route::get('/combatlog', (new AdminToolsController())->combatlog(...))->name('admin.combatlog');
                Route::get('/npc/import', (new AdminToolsController())->npcimport(...))->name('admin.tools.npc.import');
                Route::post('/npc/import', (new AdminToolsController())->npcimportsubmit(...))->name('admin.tools.npc.import.submit');
                Route::get('/npc/manage-spell-visibility/{dungeon?}', (new AdminToolsController())->manageSpellVisibility(...))->name('admin.tools.npc.managespellvisibility');
                Route::post('/npc/manage-spell-visibility/submit', (new AdminToolsController())->manageSpellVisibilitySubmit(...))->name('admin.tools.npc.managespellvisibility.submit');

                // Dungeonroute
                Route::get('/dungeonroute', (new AdminToolsController())->dungeonroute(...))->name('admin.tools.dungeonroute.view');
                Route::post('/dungeonroute', (new AdminToolsController())->dungeonroutesubmit(...))->name('admin.tools.dungeonroute.view.submit');
                Route::get('/dungeonroute/mappingversions', (new AdminToolsController())->dungeonrouteMappingVersions(...))->name('admin.tools.dungeonroute.mappingversionusage');

                // Import enemy forces
                Route::get('enemyforces/import', (new AdminToolsController())->enemyforcesimport(...))->name('admin.tools.enemyforces.import.view');
                Route::post('enemyforces/import', (new AdminToolsController())->enemyforcesimportsubmit(...))->name('admin.tools.enemyforces.import.submit');
                Route::get('enemyforces/recalculate', (new AdminToolsController())->enemyforcesrecalculate(...))->name('admin.tools.enemyforces.recalculate.view');
                Route::post('enemyforces/recalculate', (new AdminToolsController())->enemyforcesrecalculatesubmit(...))->name('admin.tools.enemyforces.recalculate.submit');

                // Thumbnails
                Route::get('thumbnails/regenerate', (new AdminToolsController())->thumbnailsregenerate(...))->name('admin.tools.thumbnails.regenerate.view');
                Route::post('thumbnails/regenerate', (new AdminToolsController())->thumbnailsregeneratesubmit(...))->name('admin.tools.thumbnails.regenerate.submit');
                Route::prefix('mdt')->group(static function () {
                    // View string contents
                    Route::get('string', (new AdminToolsController())->mdtview(...))->name('admin.tools.mdt.string.view');
                    Route::post('string', (new AdminToolsController())->mdtviewsubmit(...))->name('admin.tools.mdt.string.submit');

                    // View string contents as a dungeonroute
                    Route::get('string/dungeonroute', (new AdminToolsController())->mdtviewasdungeonroute(...))->name('admin.tools.mdt.string.viewasdungeonroute');
                    Route::post('string/dungeonroute', (new AdminToolsController())->mdtviewasdungeonroutesubmit(...))->name('admin.tools.mdt.string.viewasdungeonroute.submit');

                    // View dungeonroute as string
                    Route::get('dungeonroute/string', (new AdminToolsController())->mdtviewasstring(...))->name('admin.tools.mdt.dungeonroute.viewasstring');
                    Route::post('dungeonroute/string', (new AdminToolsController())->mdtviewasstringsubmit(...))->name('admin.tools.mdt.dungeonroute.viewasstring.submit');

                    // View mapping hash
                    Route::get('dungeonmappinghash', (new AdminToolsController())->mdtdungeonmappinghash(...))->name('admin.tools.mdt.dungeonmappinghash');
                    Route::post('dungeonmappinghash', (new AdminToolsController())->mdtdungeonmappinghashsubmit(...))->name('admin.tools.mdt.dungeonmappinghash.submit');

                    // Convert Mapping Version to MDT Mapping
                    Route::get('dungeonmappingversiontomdtmapping', (new AdminToolsController())->dungeonmappingversiontomdtmapping(...))->name('admin.tools.mdt.dungeonmappingversiontomdtmapping');
                    Route::post('dungeonmappingversiontomdtmapping', (new AdminToolsController())->dungeonmappingversiontomdtmappingsubmit(...))->name('admin.tools.mdt.dungeonmappingversiontomdtmapping.submit');
                });

                // Wow.tools
                Route::get('wowtools/importingamecoordinates', (new AdminToolsController())->importingamecoordinates(...))->name('admin.tools.wowtools.import_ingame_coordinates');
                Route::post('wowtools/importingamecoordinates', (new AdminToolsController())->importingamecoordinatessubmit(...))->name('admin.tools.wowtools.import_ingame_coordinates.submit');

                // Feature management
                Route::get('features', (new AdminToolsController())->listFeatures(...))->name('admin.tools.features.list');
                Route::post('features/toggle', (new AdminToolsController())->toggleFeature(...))->name('admin.tools.features.toggle');
                Route::post('features/forget', (new AdminToolsController())->forgetFeature(...))->name('admin.tools.features.forget');


                // Exception thrower
                Route::get('exception', (new AdminToolsController())->exceptionselect(...))->name('admin.tools.exception.select');
                Route::post('exception', (new AdminToolsController())->exceptionselectsubmit(...))->name('admin.tools.exception.select.submit');
                Route::get('mdt/diff', (new AdminToolsController())->mdtdiff(...))->name('admin.tools.mdt.diff');
                Route::get('cache/drop', (new AdminToolsController())->dropcache(...))->name('admin.tools.cache.drop');
                Route::get('mapping/forcesync', (new AdminToolsController())->mappingForceSync(...))->name('admin.tools.mapping.forcesync');
                Route::get('datadump/exportdungeondata', (new AdminToolsController())->exportdungeondata(...))->name('admin.tools.datadump.exportdungeondata');
                Route::get('datadump/exportreleases', (new AdminToolsController())->exportreleases(...))->name('admin.tools.datadump.exportreleases');
            });
        });
    });
    Route::prefix('ajax')->middleware('ajax')->group(static function () {
        Route::get('refresh-csrf', (new AjaxSiteController())->refreshCsrf(...))->name('api.refresh_csrf');

        Route::prefix('tag')->group(static function () {
            Route::get('/', (new AjaxTagController())->all(...))->name('ajax.tag.all');
            Route::get('/{category}', (new AjaxTagController())->get(...))->name('ajax.tag.list');
            Route::post('/', (new AjaxTagController())->store(...))->name('ajax.tag.create');
            Route::delete('/{tag}', (new AjaxTagController())->delete(...))->name('ajax.tag.delete');

            // Profile
            Route::put('/{tag}/all', (new AjaxTagController())->updateAll(...))->name('ajax.tag.updateall');
            Route::delete('/{tag}/all', (new AjaxTagController())->deleteAll(...))->name('ajax.tag.deleteall');
        });
        Route::prefix('heatmap')->group(static function () {
            Route::post('/data', (new AjaxHeatmapController())->getData(...))->name('ajax.heatmap.data');
        });

        Route::get('/{publickey}/data', (new AjaxDungeonRouteController())->data(...));

        Route::post('userreport/dungeonroute/{dungeonroute}', (new AjaxUserReportController())->dungeonrouteStore(...))->name('ajax.userreport.dungeonroute');
        Route::post('userreport/enemy/{enemy}', (new AjaxUserReportController())->enemyStore(...))->name('ajax.userreport.enemy');

        Route::get('/routes', (new AjaxDungeonRouteController())->get(...));

        Route::get('/search', (new AjaxDungeonRouteController())->htmlsearch(...));
        Route::get('/search/{category}', (new AjaxDungeonRouteController())->htmlsearchcategory(...));

        Route::post('/mdt/details', (new MDTImportController())->details(...))->name('mdt.details');

        Route::post('/profile/legal', (new AjaxProfileController())->legalAgree(...));

        // Metrics
        Route::prefix('metric')->group(static function () {
            Route::post('/', (new AjaxMetricController())->store(...))->name('ajax.metric.store');
            Route::post('/route/{dungeonRoute}', (new AjaxMetricController())->storeDungeonRoute(...))->name('ajax.metric.dungeonroute.store');
        });

        // Must be an admin to perform these actions
        Route::middleware(['auth', 'role:admin'])->group(static function () {
            Route::prefix('admin')->group(static function () {
                Route::get('/user', (new AjaxUserController())->get(...));
                Route::get('/npc', (new AjaxNpcController())->get(...));
                Route::post('/thumbnail/{dungeonroute}/refresh', (new AjaxDungeonRouteController())->refreshThumbnail(...));

                Route::put('/spell/{spell}', (new AjaxSpellController())->update(...));

                Route::prefix('mappingVersion/{mappingVersion}')->group(static function () {
                    Route::patch('/', (new AjaxMappingVersionController())->store(...));
                    Route::post('/enemy', (new AjaxEnemyController())->store(...));
                    Route::put('/enemy/{enemy}', (new AjaxEnemyController())->store(...));
                    Route::delete('/enemy/{enemy}', (new AjaxEnemyController())->delete(...));

                    Route::post('/enemypack', (new AjaxEnemyPackController())->store(...));
                    Route::put('/enemypack/{enemyPack}', (new AjaxEnemyPackController())->store(...));
                    Route::delete('/enemypack/{enemyPack}', (new AjaxEnemyPackController())->delete(...));

                    Route::post('/enemypatrol', (new AjaxEnemyPatrolController())->store(...))->name('ajax.admin.enemypatrol.create');
                    Route::put('/enemypatrol/{enemyPatrol}', (new AjaxEnemyPatrolController())->store(...))->name('ajax.admin.enemypatrol.update');
                    Route::delete('/enemypatrol/{enemyPatrol}', (new AjaxEnemyPatrolController())->delete(...))->name('ajax.admin.enemypatrol.delete');

                    Route::post('/dungeonfloorswitchmarker', (new AjaxDungeonFloorSwitchMarkerController())->store(...));
                    Route::put('/dungeonfloorswitchmarker/{dungeonFloorSwitchMarker}', (new AjaxDungeonFloorSwitchMarkerController())->store(...));
                    Route::delete('/dungeonfloorswitchmarker/{dungeonFloorSwitchMarker}', (new AjaxDungeonFloorSwitchMarkerController())->delete(...));

                    Route::post('/mapicon', (new AjaxMapIconController())->adminStore(...));
                    Route::put('/mapicon/{mapIcon}', (new AjaxMapIconController())->adminStore(...));
                    Route::delete('/mapicon/{mapIcon}', (new AjaxMapIconController())->adminDelete(...));

                    Route::post('/mountablearea', (new AjaxMountableAreaController())->store(...));
                    Route::put('/mountablearea/{mountableArea}', (new AjaxMountableAreaController())->store(...));
                    Route::delete('/mountablearea/{mountableArea}', (new AjaxMountableAreaController())->delete(...));

                    Route::post('/floorunion', (new AjaxFloorUnionController())->store(...));
                    Route::put('/floorunion/{floorUnion}', (new AjaxFloorUnionController())->store(...));
                    Route::delete('/floorunion/{floorUnion}', (new AjaxFloorUnionController())->delete(...));

                    Route::post('/floorunionarea', (new AjaxFloorUnionAreaController())->store(...));
                    Route::put('/floorunionarea/{floorUnionArea}', (new AjaxFloorUnionAreaController())->store(...));
                    Route::delete('/floorunionarea/{floorUnionArea}', (new AjaxFloorUnionAreaController())->delete(...));
                });
            });
            Route::put('/userreport/{userreport}/status', (new AjaxUserReportController())->status(...));
            Route::post('/tools/mdt/diff/apply', (new AdminToolsController())->applychange(...));
            Route::put('/user/{user}/patreon/benefits', (new UserController())->storePatreonBenefits(...));
        });
        Route::prefix('dungeonRoute')->group(static function () {
            Route::post('/data', (new AjaxDungeonRouteController())->getDungeonRoutesData(...));
        });

        // May be performed without being logged in (sandbox functionality)
        Route::prefix('{dungeonRoute}')->group(static function () {
            Route::post('/brushline', (new AjaxBrushlineController())->store(...))->name('ajax.dungeonroute.brushline.create');
            Route::put('/brushline/{brushline}', (new AjaxBrushlineController())->store(...))->name('ajax.dungeonroute.brushline.update');
            Route::delete('/brushline/{brushline}', (new AjaxBrushlineController())->delete(...))->name('ajax.dungeonroute.brushline.delete');

            Route::put('/killzone/mass', (new AjaxKillZoneController())->storeAll(...));
            Route::post('/killzone', (new AjaxKillZoneController())->store(...));
            Route::put('/killzone/{killZone}', (new AjaxKillZoneController())->store(...));
            Route::delete('/killzone/{killZone}', (new AjaxKillZoneController())->delete(...));
            Route::delete('/killzone', (new AjaxKillZoneController())->deleteAll(...));

            Route::post('/mapicon', (new AjaxMapIconController())->store(...));
            Route::put('/mapicon/{mapIcon}', (new AjaxMapIconController())->store(...));
            Route::delete('/mapicon/{mapIcon}', (new AjaxMapIconController())->delete(...));

            Route::post('/pridefulenemy/{enemy}', (new AjaxPridefulEnemyController())->store(...));
            Route::delete('/pridefulenemy/{enemy}', (new AjaxPridefulEnemyController())->delete(...));

            Route::post('/path', (new AjaxPathController())->store(...))->name('ajax.dungeonroute.path.create');
            Route::put('/path/{path}', (new AjaxPathController())->store(...))->name('ajax.dungeonroute.path.update');
            Route::delete('/path/{path}', (new AjaxPathController())->delete(...))->name('ajax.dungeonroute.path.delete');

            Route::post('/raidmarker/{enemy}', (new AjaxEnemyController())->setRaidMarker(...));

            Route::post('/clone/team/{team}', (new AjaxDungeonRouteController())->cloneToTeam(...));

            Route::get('/mdtExport', (new AjaxDungeonRouteController())->mdtExport(...))->name('api.dungeonroute.mdtexport');

            Route::post('/simulate', (new AjaxDungeonRouteController())->simulate(...))->name('api.dungeonroute.simulate');
        });

        // Must be logged in to perform these actions
        Route::middleware(['auth', 'role:user|admin'])->group(static function () {
            Route::prefix('{dungeonRoute}')->group(static function () {
                Route::patch('/', (new AjaxDungeonRouteController())->store(...))->name('api.dungeonroute.update');
                Route::patch('/pullgradient', (new AjaxDungeonRouteController())->storePullGradient(...))->name('api.dungeonroute.pullgradient.update');
                Route::delete('/', (new AjaxDungeonRouteController())->delete(...))->name('api.dungeonroute.delete');
                Route::post('/favorite', (new AjaxDungeonRouteController())->favorite(...))->name('api.dungeonroute.favorite');
                Route::delete('/favorite', (new AjaxDungeonRouteController())->favoriteDelete(...))->name('api.dungeonroute.favorite.delete');

                Route::post('/publishedState', (new AjaxDungeonRouteController())->publishedState(...))->name('api.dungeonroute.publishedstate');

                Route::post('/rate', (new AjaxDungeonRouteController())->rate(...))->name('api.dungeonroute.rate');
                Route::delete('/rate', (new AjaxDungeonRouteController())->rateDelete(...))->name('api.dungeonroute.rate.delete');

                Route::post('/migrate/{seasonalType}', (new AjaxDungeonRouteController())->migrateToSeasonalType(...));

                Route::prefix('/live/{liveSession}')->group(static function () {
                    Route::delete('/', (new AjaxLiveSessionController())->delete(...));
                    Route::post('/overpulledenemy', (new AjaxOverpulledEnemyController())->store(...));
                    Route::delete('/overpulledenemy', (new AjaxOverpulledEnemyController())->delete(...));
                });
            });
            Route::prefix('echo')->group(static function () {
                // Echo controller misc
                Route::get('{dungeonRoute}/members', (new AjaxEchoController())->members(...));
            });
            // Teams
            Route::prefix('team/{team}')->group(static function () {
                Route::put('/changedefaultrole', (new AjaxTeamController())->changeDefaultRole(...));
                Route::put('/changerole', (new AjaxTeamController())->changeRole(...));
                Route::post('/route/{dungeonroute}', (new AjaxTeamController())->addRoute(...));
                Route::delete('/member/{user}', (new AjaxTeamController())->removeMember(...));
                Route::delete('/route/{dungeonroute}', (new AjaxTeamController())->removeRoute(...));
                Route::get('/refreshlink', (new AjaxTeamController())->refreshInviteLink(...));
                // Ad-free giveaway
                Route::post('/member/{user}/adfree', (new AjaxTeamController())->addAdFreeGiveaway(...));
                Route::delete('/member/{user}/adfree', (new AjaxTeamController())->removeAdFreeGiveaway(...));
            });
            // User
            Route::prefix('user/{publicKey}')->group(static function () {
                Route::put('/', (new AjaxUserController())->store(...));
            });
        });
    });
    // At the bottom to let routes such as profile/routes pass through first
    Route::get('profile/{user}', (new ProfileController())->view(...))->name('profile.view');
    // View any dungeon route (catch all)
    Route::prefix('/route/{dungeon}/{dungeonroute}')->group(static function () {
        Route::get('/', (new DungeonRouteController())->view(...))->name('dungeonroute.viewnotitle');
        Route::prefix('{title?}')->group(static function () {
            Route::get('/', (new DungeonRouteController())->view(...))->name('dungeonroute.view');
            Route::get('present/', (new DungeonRouteController())->present(...))->name('dungeonroute.present');
            Route::get('present/{floorindex}', (new DungeonRouteController())->presentFloor(...))->name('dungeonroute.present.floor');
            Route::get('embed/', (new DungeonRouteController())->embed(...))->name('dungeonroute.embed');
            Route::get('embed/{floorindex}', (new DungeonRouteController())->embed(...))->name('dungeonroute.embed.floor');
            Route::get('{floorindex}', (new DungeonRouteController())->viewFloor(...))->name('dungeonroute.view.floor');
            // Preview of a route for image capturing library
            Route::get('preview/{floorIndex}', (new DungeonRouteController())->preview(...))->name('dungeonroute.preview');
        });
    });
    Route::prefix('{dungeonroute}')->group(static function () {
        Route::get('/', (new DungeonRouteLegacyController())->viewold(...))->name('dungeonroute.viewold');
        Route::get('embed/', (new DungeonRouteLegacyController())->embedold(...));
        Route::get('embed/{floorindex}', (new DungeonRouteLegacyController())->embedold(...));
        Route::get('{floorindex}', (new DungeonRouteLegacyController())->viewfloorold(...));
        // Preview of a route for image capturing library
        Route::get('preview/{floorindex}', (new DungeonRouteLegacyController())->previewold(...));
    });
});

Auth::routes();
