<?php

use App\Http\Controllers\Api\V1\APICombatLogController;
use App\Http\Controllers\Api\V1\APIDungeonController;
use App\Http\Controllers\Api\V1\APIDungeonRouteController;
use App\Http\Controllers\Api\V1\APIDungeonRouteThumbnailJobController;

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
Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'combatlog'], function () {
        Route::post('route', (new APICombatLogController())->createRoute(...))->name('api.v1.combatlog.route.create');
    });

    Route::group(['prefix' => 'route'], function () {
        Route::get('/', (new APIDungeonRouteController())->list(...))->name('api.v1.route.list');

        Route::post('/{dungeonRoute}/thumbnail', (new APIDungeonRouteController())->createThumbnails(...))->name('api.v1.route.thumbnail.create');

        Route::get('/thumbnailJob/{dungeonRouteThumbnailJob}', (new APIDungeonRouteThumbnailJobController())->get(...))->name('api.v1.thumbnailjob.get');
    });

    // Static data
    Route::group(['prefix' => 'dungeon'], function () {
        Route::get('/', (new APIDungeonController())->list(...))->name('api.v1.combatlog.dungeon.list');
    });
});
