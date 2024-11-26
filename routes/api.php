<?php

use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogController;
use App\Http\Controllers\Api\V1\Public\Dungeon\APIDungeonController;
use App\Http\Controllers\Api\V1\Public\Route\APIDungeonRouteController;
use App\Http\Controllers\Api\V1\Public\Route\APIDungeonRouteThumbnailJobController;

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
Route::prefix('v1')->group(static function () {
    Route::prefix('combatlog')->group(static function () {
        Route::prefix('route')->group(static function () {
            Route::post('/', (new APICombatLogController())->createRoute(...))->name('api.v1.combatlog.route.create');
        });
        Route::prefix('event')->group(static function () {
            Route::post('correct', (new APICombatLogController())->correctEvents(...))->name('api.v1.combatlog.event.correct');
        });
    });

    Route::prefix('route')->group(static function () {
        Route::get('/', (new APIDungeonRouteController())->get(...))->name('api.v1.route.list');
        Route::post('/{dungeonRoute}/thumbnail', (new APIDungeonRouteController())->createThumbnails(...))->name('api.v1.route.thumbnail.create');
        Route::get('/thumbnailJob/{dungeonRouteThumbnailJob}', (new APIDungeonRouteThumbnailJobController())->get(...))->name('api.v1.thumbnailjob.get');
    });

    // Static data
    Route::prefix('dungeon')->group(static function () {
        Route::get('/', (new APIDungeonController())->get(...))->name('api.v1.combatlog.dungeon.list');
    });
});
