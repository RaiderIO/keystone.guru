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

Auth::routes();

Route::group(['middleware' => ['viewcachebuster', 'admindebugbar']], function () {

    // Catch for hard-coded /home route in RedirectsUsers.php
    Route::get('home', function () {
        return redirect('/');
    });

    Route::get('credits', function () {
        return view('misc.credits');
    })->name('misc.credits');

    Route::get('about', function () {
        return view('misc.about');
    })->name('misc.about');

    Route::get('privacy', function () {
        return view('legal.privacy');
    })->name('legal.privacy');

    Route::get('terms', function () {
        return view('legal.terms');
    })->name('legal.terms');

    Route::get('cookies', function () {
        return view('legal.cookies');
    })->name('legal.cookies');

    Route::get('/', 'HomeController@index')->name('home');

    Route::get('changelog', function () {
        return view('misc.changelog');
    })->name('misc.changelog');

    Route::get('mapping', function () {
        return view('misc.mapping');
    })->name('misc.mapping');

    Route::get('affixes', function () {
        return view('misc.affixes');
    })->name('misc.affixes');

    Route::get('try', 'DungeonRouteController@try')->name('dungeonroute.try');
    Route::post('try', 'DungeonRouteController@try')->name('dungeonroute.try.post');

    // ['auth', 'role:admin|user']

    Route::get('patreon-unlink', 'PatreonController@unlink')->name('patreon.unlink');
    Route::get('patreon-link', 'PatreonController@link')->name('patreon.link');
    Route::get('patreon-oauth', 'PatreonController@oauth_redirect')->name('patreon.oauth.redirect');

    Route::get('profile/(user}', 'ProfileController@view')->name('profile.view');

    Route::post('userreport/new', 'UserReportController@store')->name('userreport.new');

    Route::get('dungeonroutes', 'DungeonRouteController@list')->name('dungeonroutes');

    Route::group(['middleware' => ['auth', 'role:user|admin']], function () {
        // Must be logged in to create a new dungeon route
        Route::get('new', 'DungeonRouteController@new')->name('dungeonroute.new');
        Route::post('new', 'DungeonRouteController@savenew')->name('dungeonroute.savenew');

        // Legacy redirects
        Route::get('edit/{dungeonroute}', function (\App\Models\DungeonRoute $dungeonroute) {
            return redirect(route('dungeonroute.edit', ['dungeonroute' => $dungeonroute->public_key]));
        });
        Route::patch('edit/{dungeonroute}', function (\App\Models\DungeonRoute $dungeonroute) {
            return redirect(route('dungeonroute.update', ['dungeonroute' => $dungeonroute->public_key]));
        });

        // Edit your own dungeon routes
        Route::get('{dungeonroute}/edit', 'DungeonRouteController@edit')
            ->middleware('can:edit,dungeonroute')
            ->name('dungeonroute.edit');
        // Submit a patch for your own dungeon route
        Route::patch('{dungeonroute}/edit', 'DungeonRouteController@update')
            ->middleware('can:edit,dungeonroute')
            ->name('dungeonroute.update');
        // Clone a route
        Route::get('{dungeonroute}/clone', 'DungeonRouteController@clone')
            ->middleware('can:clone,dungeonroute')
            ->name('dungeonroute.clone');

        Route::get('profile', 'ProfileController@edit')->name('profile.edit');
        Route::patch('profile/{user}', 'ProfileController@update')->name('profile.update');
        Route::patch('profile', 'ProfileController@changepassword')->name('profile.changepassword');
    });

    Route::group(['middleware' => ['auth', 'role:admin']], function () {
        // Only admins may view a list of profiles
        Route::get('profiles', 'ProfileController@list')->name('profile.list');

        // Dungeons
        Route::get('admin/dungeon/new', 'DungeonController@new')->name('admin.dungeon.new');
        Route::get('admin/dungeon/{dungeon}', 'DungeonController@edit')->name('admin.dungeon.edit');

        Route::post('admin/dungeon/new', 'DungeonController@savenew')->name('admin.dungeon.savenew');
        Route::patch('admin/dungeon/{dungeon}', 'DungeonController@update')->name('admin.dungeon.update');

        Route::get('admin/dungeons', 'DungeonController@list')->name('admin.dungeons');

        // Floors
        Route::get('admin/floor/new', 'FloorController@new')->name('admin.floor.new')->where(['dungeon' => '[0-9]+']);
        Route::get('admin/floor/{floor}', 'FloorController@edit')->name('admin.floor.edit');

        Route::post('admin/floor/new', 'FloorController@savenew')->name('admin.floor.savenew')->where(['dungeon' => '[0-9]+']);
        Route::patch('admin/floor/{floor}', 'FloorController@update')->name('admin.floor.update');

        // Expansions
        Route::get('admin/expansion/new', 'ExpansionController@new')->name('admin.expansion.new');
        Route::get('admin/expansion/{expansion}', 'ExpansionController@edit')->name('admin.expansion.edit');

        Route::post('admin/expansion/new', 'ExpansionController@savenew')->name('admin.expansion.savenew');
        Route::patch('admin/expansion/{expansion}', 'ExpansionController@update')->name('admin.expansion.update');

        Route::get('admin/expansions', 'ExpansionController@list')->name('admin.expansions');

        // NPCs
        Route::get('admin/npc/new', 'NpcController@new')->name('admin.npc.new');
        Route::get('admin/npc/{npc}', 'NpcController@edit')->name('admin.npc.edit');

        Route::post('admin/npc/new', 'NpcController@savenew')->name('admin.npc.savenew');
        Route::patch('admin/npc/{npc}', 'NpcController@update')->name('admin.npc.update');

        Route::get('admin/npcs', 'NpcController@list')->name('admin.npcs');

        Route::get('admin/users', 'UserController@list')->name('admin.users');
        Route::post('admin/user/{user}/makeadmin', 'UserController@makeadmin')->name('admin.user.makeadmin');
        Route::post('admin/user/{user}/makeuser', 'UserController@makeuser')->name('admin.user.makeuser');

        Route::get('admin/userreports', 'UserReportController@list')->name('admin.userreports');

        Route::get('admin/datadump/exportdungeondata', 'ExportDungeonDataController@view')->name('admin.datadump.exportdungeondata');
        Route::post('admin/datadump/exportdungeondata', 'ExportDungeonDataController@submit')->name('admin.datadump.viewexporteddungeondata');
    });


    Route::group(['prefix' => 'ajax', 'middleware' => 'ajax'], function () {
        Route::get('/enemypacks', 'APIEnemyPackController@list');

        Route::get('/enemies', 'APIEnemyController@list');

        Route::get('/enemypatrols', 'APIEnemyPatrolController@list');

        Route::get('/dungeonroutes', 'APIDungeonRouteController@list')->name('api.dungeonroutes');

        Route::get('/routes', 'APIRouteController@list')->where(['dungeonroute' => '[a-zA-Z0-9]+'])->where(['floor_id' => '[0-9]+']);

        Route::get('/killzones', 'APIKillZoneController@list')->where(['dungeonroute' => '[a-zA-Z0-9]+'])->where(['floor_id' => '[0-9]+']);

        Route::get('/mapcomments', 'APIMapCommentController@list')->where(['dungeonroute' => '[a-zA-Z0-9]+'])->where(['floor_id' => '[0-9]+']);

        Route::get('/dungeonstartmarkers', 'APIDungeonStartMarkerController@list');

        Route::get('/dungeonfloorswitchmarkers', 'APIDungeonFloorSwitchMarkerController@list')->where(['floor_id' => '[0-9]+']);

        Route::group(['middleware' => ['auth', 'role:user']], function () {
            Route::post('/route', 'APIRouteController@store');
            Route::delete('/route', 'APIRouteController@delete');

            Route::post('/dungeonroute/{dungeonroute}/killzone', 'APIKillZoneController@store');
            Route::delete('/dungeonroute/{dungeonroute}/killzone/{killzone}', 'APIKillZoneController@delete');

            Route::post('/mapcomment', 'APIMapCommentController@store');
            Route::delete('/mapcomment', 'APIMapCommentController@delete');

            Route::post('/enemy/{enemy}/raidmarker', 'APIEnemyController@setRaidMarker');
            Route::post('/enemy/{enemy}/infested', 'APIEnemyController@setInfested');

            Route::patch('/dungeonroute/{dungeonroute}', 'APIDungeonRouteController@store')->name('api.dungeonroute.update');
            Route::post('/dungeonroute/{dungeonroute}/publish', 'APIDungeonRouteController@publish')
                ->middleware('can:publish,dungeonroute')
                ->name('api.dungeonroute.publish');
            Route::post('/dungeonroute/{dungeonroute}/rate', 'APIDungeonRouteController@rate')
                ->middleware('can:rate,dungeonroute')
                ->name('api.dungeonroute.rate');

            // Submit a patch for your own dungeon route
            Route::delete('/dungeonroute/{dungeonroute}', 'APIDungeonRouteController@delete')
                ->middleware('can:delete,dungeonroute')
                ->name('api.dungeonroute.delete');
            Route::delete('/dungeonroute/{dungeonroute}/rate', 'APIDungeonRouteController@rateDelete')->name('api.dungeonroute.rate.delete');

            Route::post('/dungeonroute/{dungeonroute}/favorite', 'APIDungeonRouteController@favorite')->name('api.dungeonroute.favorite');
            Route::delete('/dungeonroute/{dungeonroute}/favorite', 'APIDungeonRouteController@favoriteDelete')->name('api.dungeonroute.favorite.delete');


        });

        Route::group(['middleware' => ['auth', 'role:admin']], function () {
            Route::post('/enemypack', 'APIEnemyPackController@store');
            Route::delete('/enemypack', 'APIEnemyPackController@delete');

            Route::post('/enemy', 'APIEnemyController@store');
            Route::delete('/enemy', 'APIEnemyController@delete');

            Route::post('/enemypatrol', 'APIEnemyPatrolController@store');
            Route::delete('/enemypatrol', 'APIEnemyPatrolController@delete');

            Route::post('/dungeonstartmarker', 'APIDungeonStartMarkerController@store')->where(['dungeon' => '[0-9]+']);
            Route::delete('/dungeonstartmarker', 'APIDungeonStartMarkerController@delete');

            Route::post('/dungeonfloorswitchmarker', 'APIDungeonFloorSwitchMarkerController@store')->where(['floor_id' => '[0-9]+']);
            Route::delete('/dungeonfloorswitchmarker', 'APIDungeonFloorSwitchMarkerController@delete');

            Route::post('/userreport/{userreport}/markasresolved', 'APIUserReportController@markasresolved');
        });
    });

    // View any dungeon route (catch all)
    Route::get('{dungeonroute}', 'DungeonRouteController@view')
        ->name('dungeonroute.view');

});