<?php

use App\Http\Controllers\Api\V1\APICombatLogController;
use App\Http\Controllers\Api\V1\APIDungeonController;
use App\Http\Controllers\Api\V1\APIDungeonRouteController;

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
        Route::post('route', [APICombatLogController::class, 'createRoute'])->name('api.v1.combatlog.route.create');
    });

    Route::group(['prefix' => 'route'], function () {
        Route::get('/', [APIDungeonRouteController::class, 'list'])->name('api.v1.route.list');

        Route::post('/{dungeonRoute}/thumbnail', [APIDungeonRouteController::class, 'createThumbnails'])->name('api.v1.route.thumbnail.create');
    });

    // Static data
    Route::group(['prefix' => 'dungeon'], function () {
        Route::get('/', [APIDungeonController::class, 'list'])->name('api.v1.combatlog.dungeon.list');
    });
});
