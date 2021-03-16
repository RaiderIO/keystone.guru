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

use App\Http\Controllers\Auth\BattleNetLoginController;
use App\Http\Controllers\Auth\DiscordLoginController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\DungeonRouteController;
use App\Http\Controllers\DungeonRouteDiscoverController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\SiteController;

Auth::routes();

Route::group(['middleware' => ['viewcachebuster']], function ()
{

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

    Route::get('mapping', [SiteController::class, 'mapping'])->name('misc.mapping');

    Route::get('affixes', [SiteController::class, 'affixes'])->name('misc.affixes');

    Route::get('demo', [SiteController::class, 'demo'])->name('misc.demo');

    Route::get('timetest', [SiteController::class, 'timetest'])->name('misc.timetest');

    Route::get('embed/{dungeonroute}', [SiteController::class, 'embed'])->name('misc.embed');

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

    // Edit your own dungeon routes
    Route::get('{dungeonroute}/edit', [DungeonRouteController::class, 'edit'])->name('dungeonroute.edit');
    Route::get('{dungeonroute}/edit/{floor}', [DungeonRouteController::class, 'editfloor'])->name('dungeonroute.edit.floor');
    // Submit a patch for your own dungeon route
    Route::patch('{dungeonroute}/edit', [DungeonRouteController::class, 'update'])->name('dungeonroute.update');

    Route::post('new/mdtimport', 'MDTImportController@import')->name('dungeonroute.new.mdtimport');

    // ['auth', 'role:admin|user']

    Route::get('patreon-unlink', 'PatreonController@unlink')->name('patreon.unlink');
    Route::get('patreon-link', 'PatreonController@link')->name('patreon.link');
    Route::get('patreon-oauth', 'PatreonController@oauth_redirect')->name('patreon.oauth.redirect');

    Route::get('dungeonroutes', 'SiteController@dungeonroutes');
    Route::get('routes', [DungeonRouteDiscoverController::class, 'discover'])->name('dungeonroutes');
    Route::get('search', [DungeonRouteDiscoverController::class, 'search'])->name('dungeonroutes.search');
    Route::get('routes/popular', [DungeonRouteDiscoverController::class, 'discoverpopular'])->name('dungeonroutes.popular');
    Route::get('routes/affixes/current', [DungeonRouteDiscoverController::class, 'discoverthisweek'])->name('dungeonroutes.thisweek');
    Route::get('routes/affixes/next', [DungeonRouteDiscoverController::class, 'discovernextweek'])->name('dungeonroutes.nextweek');
    Route::get('routes/new', [DungeonRouteDiscoverController::class, 'discovernew'])->name('dungeonroutes.new');

    Route::get('routes/{dungeon}', [DungeonRouteDiscoverController::class, 'discoverdungeon'])->name('dungeonroutes.discoverdungeon');
    Route::get('routes/{dungeon}/popular', [DungeonRouteDiscoverController::class, 'discoverdungeonpopular'])->name('dungeonroutes.discoverdungeon.popular');
    Route::get('routes/{dungeon}/affixes/current', [DungeonRouteDiscoverController::class, 'discoverdungeonthisweek'])->name('dungeonroutes.discoverdungeon.thisweek');
    Route::get('routes/{dungeon}/affixes/next', [DungeonRouteDiscoverController::class, 'discoverdungeonnextweek'])->name('dungeonroutes.discoverdungeon.nextweek');
    Route::get('routes/{dungeon}/new', [DungeonRouteDiscoverController::class, 'discoverdungeonnew'])->name('dungeonroutes.discoverdungeon.new');

    // May be accessed without being logged in
    Route::get('team/invite/{invitecode}', 'TeamController@invite')->name('team.invite');

    Route::group(['middleware' => ['auth', 'role:user|admin']], function ()
    {
        // Legacy redirects
        Route::get('edit/{dungeonroute}', [DungeonRouteController::class, 'editLegacy']);
        Route::patch('edit/{dungeonroute}', [DungeonRouteController::class, 'updateLegacy']);

        // Clone a route
        Route::get('{dungeonroute}/clone', [DungeonRouteController::class, 'clone'])->name('dungeonroute.clone');
        // Claiming a route that was made by /sandbox functionality
        Route::get('{dungeonroute}/claim', [DungeonRouteController::class, 'claim'])->name('dungeonroute.claim');

        Route::get('profile', 'ProfileController@edit')->name('profile.edit');
        Route::get('profile/routes', 'ProfileController@routes')->name('profile.routes');
        Route::get('profile/tags', 'ProfileController@tags')->name('profile.tags');
        Route::patch('profile/{user}', 'ProfileController@update')->name('profile.update');
        Route::delete('profile/delete', 'ProfileController@delete')->name('profile.delete');
        Route::patch('profile/{user}/privacy', 'ProfileController@updatePrivacy')->name('profile.updateprivacy');
        Route::patch('profile', 'ProfileController@changepassword')->name('profile.changepassword');
        Route::post('profile/tag', 'ProfileController@createtag')->name('profile.tag.create');

        Route::get('teams', 'TeamController@list')->name('team.list');
        Route::get('team/new', 'TeamController@new')->name('team.new');
        Route::get('team/{team}', 'TeamController@edit')->name('team.edit');
        Route::delete('team/{team}', 'TeamController@delete')->name('team.delete');
        Route::post('team/tag', 'TeamController@createtag')->name('team.tag.create');

        Route::post('team/new', 'TeamController@savenew')->name('team.savenew');
        Route::patch('team/{team}', 'TeamController@update')->name('team.update');
        Route::get('team/invite/{invitecode}/accept', 'TeamController@inviteaccept')->name('team.invite.accept');
    });

    Route::group(['middleware' => ['auth', 'role:admin']], function ()
    {
        // Only admins may view a list of profiles
        Route::get('profiles', 'ProfileController@list')->name('profile.list');

        Route::get('phpinfo', 'SiteController@phpinfo')->name('misc.phpinfo');

        Route::group(['prefix' => 'admin'], function ()
        {
            // Dungeons
            Route::get('dungeon/new', 'DungeonController@new')->name('admin.dungeon.new');
            Route::get('dungeon/{dungeon}', 'DungeonController@edit')->name('admin.dungeon.edit');

            Route::post('dungeon/new', 'DungeonController@savenew')->name('admin.dungeon.savenew');
            Route::patch('dungeon/{dungeon}', 'DungeonController@update')->name('admin.dungeon.update');

            Route::get('dungeons', 'DungeonController@list')->name('admin.dungeons');

            // Floors
            Route::get('dungeon/{dungeon}/floor/new', 'FloorController@new')->name('admin.floor.new');
            Route::get('dungeon/{dungeon}/floor/{floor}', 'FloorController@edit')->name('admin.floor.edit');
            Route::get('dungeon/{dungeon}/floor/{floor}/mapping', 'FloorController@mapping')->name('admin.floor.edit.mapping');

            Route::post('dungeon/{dungeon}/floor/new', 'FloorController@savenew')->name('admin.floor.savenew');
            Route::patch('dungeon/{dungeon}/floor/{floor}', 'FloorController@update')->name('admin.floor.update');

            // Expansions
            Route::get('expansion/new', 'ExpansionController@new')->name('admin.expansion.new');
            Route::get('expansion/{expansion}', 'ExpansionController@edit')->name('admin.expansion.edit');

            Route::post('expansion/new', 'ExpansionController@savenew')->name('admin.expansion.savenew');
            Route::patch('expansion/{expansion}', 'ExpansionController@update')->name('admin.expansion.update');

            Route::get('expansions', 'ExpansionController@list')->name('admin.expansions');

            // Releases
            Route::get('release/new', 'ReleaseController@new')->name('admin.release.new');
            Route::get('release/{release}', 'ReleaseController@edit')->name('admin.release.edit');

            Route::post('release/new', 'ReleaseController@savenew')->name('admin.release.savenew');
            Route::patch('release/{release}', 'ReleaseController@update')->name('admin.release.update');

            Route::get('release', 'ReleaseController@list')->name('admin.releases');

            // NPCs
            Route::get('npc/new', 'NpcController@new')->name('admin.npc.new');
            Route::get('npc/{npc}', 'NpcController@edit')->name('admin.npc.edit');

            Route::post('npc/new', 'NpcController@savenew')->name('admin.npc.savenew');
            Route::patch('npc/{npc}', 'NpcController@update')->name('admin.npc.update');

            Route::get('npcs', 'NpcController@list')->name('admin.npcs');

            // Spells
            Route::get('spell/new', 'SpellController@new')->name('admin.spell.new');
            Route::get('spell/{spell}', 'SpellController@edit')->name('admin.spell.edit');

            Route::post('spell/new', 'SpellController@savenew')->name('admin.spell.savenew');
            Route::patch('spell/{spell}', 'SpellController@update')->name('admin.spell.update');

            Route::get('spells', 'SpellController@list')->name('admin.spells');

            Route::get('users', 'UserController@list')->name('admin.users');
            Route::post('user/{user}/makeadmin', 'UserController@makeadmin')->name('admin.user.makeadmin');
            Route::post('user/{user}/makeuser', 'UserController@makeuser')->name('admin.user.makeuser');
            Route::delete('user/{user}/delete', 'UserController@delete')->name('admin.user.delete');

            Route::get('userreports', 'UserReportController@list')->name('admin.userreports');

            Route::get('dashboard', 'AdminToolsController@dashboard')->name('admin.dashboard');

            Route::group(['prefix' => 'tools'], function ()
            {
                Route::get('/', 'AdminToolsController@index')->name('admin.tools');

                Route::get('/npcimport', 'AdminToolsController@npcimport')->name('admin.tools.npcimport');
                Route::post('/npcimport', 'AdminToolsController@npcimportsubmit')->name('admin.tools.npcimport.submit');

                // View string contents
                Route::get('mdt/string', 'AdminToolsController@mdtview')->name('admin.tools.mdt.string.view');
                Route::post('mdt/string', 'AdminToolsController@mdtviewsubmit')->name('admin.tools.mdt.string.submit');

                // View string contents as a dungeonroute
                Route::get('mdt/string/dungeonroute', 'AdminToolsController@mdtviewasdungeonroute')->name('admin.tools.mdt.string.viewasdungeonroute');
                Route::post('mdt/string/dungeonroute', 'AdminToolsController@mdtviewasdungeonroutesubmit')->name('admin.tools.mdt.string.viewasdungeonroute.submit');

                // View dungeonroute as string
                Route::get('mdt/dungeonroute/string', 'AdminToolsController@mdtviewasstring')->name('admin.tools.mdt.dungeonroute.viewasstring');
                Route::post('mdt/dungeonroute/string', 'AdminToolsController@mdtviewasstringsubmit')->name('admin.tools.mdt.dungeonroute.viewasstring.submit');

                // Exception thrower
                Route::get('exception', 'AdminToolsController@exceptionselect')->name('admin.tools.exception.select');
                Route::post('exception', 'AdminToolsController@exceptionselectsubmit')->name('admin.tools.exception.select.submit');

                Route::get('mdt/diff', 'AdminToolsController@mdtdiff')->name('admin.tools.mdt.diff');
                Route::get('cache/drop', 'AdminToolsController@dropcache')->name('admin.tools.cache.drop');

                Route::get('datadump/exportdungeondata', 'AdminToolsController@exportdungeondata')->name('admin.tools.datadump.exportdungeondata');
                Route::get('datadump/exportreleases', 'AdminToolsController@exportreleases')->name('admin.tools.datadump.exportreleases');
            });
        });

        // Dashboard
        Route::group(['prefix' => 'dashboard'], function ()
        {
            Route::get('/', 'DashboardController@index')->name('dashboard.home');
            Route::get('/users', 'DashboardController@users')->name('dashboard.users');
            Route::get('/routes', 'DashboardController@dungeonroutes')->name('dashboard.routes');
            Route::get('/teams', 'DashboardController@teams')->name('dashboard.teams');
            Route::get('/pageviews', 'DashboardController@pageviews')->name('dashboard.pageviews');

//            Route::resource('user', 'DashboardUserController', ['except' => ['show']]);
//            Route::get('profile', ['as' => 'profile.edit', 'uses' => 'DashboardProfileController@edit']);
//            Route::put('profile', ['as' => 'profile.update', 'uses' => 'DashboardProfileController@update']);
//            Route::put('profile/password', ['as' => 'profile.password', 'uses' => 'DashboardProfileController@password']);
        });
    });

    Route::group(['prefix' => 'ajax'], function ()
    {
        Route::group(['prefix' => 'tag'], function ()
        {
            Route::get('/', 'APITagController@all')->name('api.tag.all');
            Route::get('/{category}', 'APITagController@list')->name('api.tag.list');
            Route::post('/', 'APITagController@store')->name('api.tag.create');
            Route::delete('/{tag}', 'APITagController@delete')->name('api.tag.delete');
            // Profile
            Route::put('/{tag}/all', 'APITagController@updateAll')->name('api.tag.updateall');
            Route::delete('/{tag}/all', 'APITagController@deleteAll')->name('api.tag.deleteall');
        });
    });

    Route::group(['prefix' => 'ajax', 'middleware' => 'ajax'], function ()
    {
        Route::get('/{publickey}/data', 'APIDungeonRouteController@data');

        Route::post('userreport/dungeonroute/{dungeonroute}', 'APIUserReportController@dungeonrouteStore')->name('userreport.dungeonroute');
        Route::post('userreport/enemy/{enemy}', 'APIUserReportController@enemyStore')->name('userreport.enemy');

        Route::get('/routes', 'APIDungeonRouteController@list');
        Route::get('/search', 'APIDungeonRouteController@htmlsearch');

        Route::post('/mdt/details', 'MDTImportController@details')->name('mdt.details');

        Route::post('/profile/legal', 'APIProfileController@legalAgree');

        // Must be an admin to perform these actions
        Route::group(['middleware' => ['auth', 'role:admin']], function ()
        {
            Route::group(['prefix' => 'admin'], function ()
            {
                Route::get('/user', 'APIUserController@list');
                Route::get('/npc', 'APINpcController@list');

                Route::post('/enemy', 'APIEnemyController@store');
                Route::delete('/enemy/{enemy}', 'APIEnemyController@delete');

                Route::post('/enemypack', 'APIEnemyPackController@store');
                Route::delete('/enemypack/{enemypack}', 'APIEnemyPackController@delete');

                Route::post('/enemypatrol', 'APIEnemyPatrolController@store');
                Route::delete('/enemypatrol/{enemypatrol}', 'APIEnemyPatrolController@delete');

                Route::post('/dungeonfloorswitchmarker', 'APIDungeonFloorSwitchMarkerController@store')->where(['floor_id' => '[0-9]+']);
                Route::delete('/dungeonfloorswitchmarker/{dungeonfloorswitchmarker}', 'APIDungeonFloorSwitchMarkerController@delete');

                Route::post('/mapicon', 'APIMapIconController@adminStore');
                Route::delete('/mapicon/{mapicon}', 'APIMapIconController@adminDelete');
            });

            Route::put('/userreport/{userreport}/status', 'APIUserReportController@status');

            Route::post('/tools/mdt/diff/apply', 'AdminToolsController@applychange');

            Route::put('/user/{user}/patreon/paidtier', 'UserController@storepaidtiers');
        });

        // May be performed without being logged in (sandbox functionality)
        Route::group(['prefix' => '{dungeonroute}'], function ()
        {
            Route::post('/brushline', 'APIBrushlineController@store');
            Route::delete('/brushline/{brushline}', 'APIBrushlineController@delete');

            Route::post('/killzone', 'APIKillZoneController@store');
            Route::delete('/killzone/{killzone}', 'APIKillZoneController@delete');
            Route::put('/killzone', 'APIKillZoneController@storeall');
            Route::delete('/killzone', 'APIKillZoneController@deleteall');

            Route::post('/mapicon', 'APIMapIconController@store');
            Route::delete('/mapicon/{mapicon}', 'APIMapIconController@delete');

            Route::post('/pridefulenemy/{enemy}', 'APIPridefulEnemyController@store');
            Route::delete('/pridefulenemy/{enemy}', 'APIPridefulEnemyController@delete');

            Route::post('/path', 'APIPathController@store');
            Route::delete('/path/{path}', 'APIPathController@delete');

            Route::post('/raidmarker/{enemy}', 'APIEnemyController@setRaidMarker');

            // Clone a route from the 'my routes' section
            Route::post('/clone/team/{team}', 'APIDungeonRouteController@cloneToTeam');

            Route::get('/mdtExport', 'APIDungeonRouteController@mdtExport')->name('api.dungeonroute.mdtexport');
        });

        // Must be logged in to perform these actions
        Route::group(['middleware' => ['auth', 'role:user|admin']], function ()
        {

            Route::group(['prefix' => '{dungeonroute}'], function ()
            {
                Route::patch('/', 'APIDungeonRouteController@store')->name('api.dungeonroute.update');
                Route::patch('/pullgradient', 'APIDungeonRouteController@storePullGradient')->name('api.dungeonroute.pullgradient.update');
                Route::delete('/', 'APIDungeonRouteController@delete')->name('api.dungeonroute.delete');

                Route::post('/favorite', 'APIDungeonRouteController@favorite')->name('api.dungeonroute.favorite');
                Route::delete('/favorite', 'APIDungeonRouteController@favoriteDelete')->name('api.dungeonroute.favorite.delete');

                Route::post('/publishedState', 'APIDungeonRouteController@publishedState')->name('api.dungeonroute.publishedstate');

                Route::post('/rate', 'APIDungeonRouteController@rate')->name('api.dungeonroute.rate');
                Route::delete('/rate', 'APIDungeonRouteController@rateDelete')->name('api.dungeonroute.rate.delete');
            });

            Route::group(['prefix' => 'echo'], function ()
            {
                // Echo controller misc
                Route::get('{dungeonroute}/members', 'APIEchoController@members');
            });

            // Teams
            Route::group(['prefix' => 'team/{team}'], function ()
            {
                Route::post('/changerole', 'APITeamController@changeRole');
                Route::post('/route/{dungeonroute}', 'APITeamController@addRoute');
                Route::delete('/member/{user}', 'APITeamController@removeMember');
                Route::delete('/route/{dungeonroute}', 'APITeamController@removeRoute');
                Route::get('/refreshlink', 'APITeamController@refreshInviteLink');
            });
        });
    });

    // At the bottom to let routes such as profile/routes pass through first
    Route::get('profile/{user}', 'ProfileController@view')->name('profile.view');

    // View any dungeon route (catch all)
    Route::get('{dungeonroute}', [DungeonRouteController::class, 'view'])->name('dungeonroute.view');
    Route::get('{dungeonroute}/embed/', [DungeonRouteController::class, 'embed'])->name('dungeonroute.embed');
    Route::get('{dungeonroute}/embed/{floorindex}', [DungeonRouteController::class, 'embed'])->name('dungeonroute.embed.floor');
    Route::get('{dungeonroute}/{floor}', [DungeonRouteController::class, 'viewfloor'])->name('dungeonroute.view.floor');
    // Preview of a route for image capturing library
    Route::get('{dungeonroute}/preview/{floorindex}', [DungeonRouteController::class, 'preview'])->name('dungeonroute.preview');
});

Auth::routes();