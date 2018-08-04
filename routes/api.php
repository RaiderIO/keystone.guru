<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// middleware('auth:api')->
Route::group(['prefix' => 'v1'], function () {
    // TODO: authentication for this API?
    // Route::group(['middleware' => ['auth', 'role:admin']], function () {
        Route::get('/enemypacks', 'APIEnemyPackController@list');
        Route::post('/enemypack', 'APIEnemyPackController@store');
        Route::delete('/enemypack', 'APIEnemyPackController@delete');

        Route::get('/enemies', 'APIEnemyController@list');
        Route::post('/enemy', 'APIEnemyController@store');
        Route::delete('/enemy', 'APIEnemyController@delete');

        Route::patch('/dungeonroute/{dungeonroute}', 'APIDungeonRouteController@store')->name('api.dungeonroute.update');

        Route::get('/route', 'APIRouteController@get')->where(['dungeonroute' => '[0-9]+']);
        // We don't really care for route IDs, we drop everything we have and create a new route when this is called
        Route::patch('/route', 'APIRouteController@store')->where(['dungeonroute' => '[0-9]+']);

        Route::get('/dungeonstartmarkers', 'APIDungeonStartMarkerController@list');
        Route::post('/dungeonstartmarker', 'APIDungeonStartMarkerController@store')->where(['dungeon' => '[0-9]+']);
        Route::delete('/dungeonstartmarker', 'APIDungeonStartMarkerController@delete');

        Route::get('/dungeonfloorswitchmarkers', 'APIDungeonFloorSwitchMarkerController@list')->where(['floor_id' => '[0-9]+']);
        Route::post('/dungeonfloorswitchmarker', 'APIDungeonFloorSwitchMarkerController@store')->where(['floor_id' => '[0-9]+']);
        Route::delete('/dungeonfloorswitchmarker', 'APIDungeonFloorSwitchMarkerController@delete');
    // });
});
