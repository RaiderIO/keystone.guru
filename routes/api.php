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
        Route::middleware('throttle:api-combatlog-create-dungeonroute')->prefix('route')->group(static function () {
            Route::post('/', (new APICombatLogController())->store(...))->name('api.v1.combatlog.route.store');
        });
        Route::middleware('throttle:api-combatlog-correct-event')->prefix('event')->group(static function () {
            Route::post('correct', (new APICombatLogController())->correctEvents(...))->name('api.v1.combatlog.event.correct');
        });
    });

    Route::prefix('route')->group(static function () {
        Route::get('/', (new APIDungeonRouteController())->index(...))->name('api.v1.route.index');
        Route::prefix('{dungeonRoute}')->middleware('can:view,dungeonRoute')->group(static function () {
            Route::get('/', (new APIDungeonRouteController())->show(...))->name('api.v1.route.show');

            Route::middleware('throttle:api-create-dungeonroute-thumbnail')->group(static function () {
                Route::post('/thumbnail', (new APIDungeonRouteController())->storeThumbnails(...))->name('api.v1.route.thumbnail.store');
            });
        });
        Route::get('/thumbnailJob/{dungeonRouteThumbnailJob}', (new APIDungeonRouteThumbnailJobController())->show(...))->name('api.v1.thumbnailjob.show');
    });

    // Static data
    Route::prefix('dungeon')->group(static function () {
        Route::get('/', (new APIDungeonController())->index(...))->name('api.v1.combatlog.dungeon.index');
        Route::get('/{dungeon}', (new APIDungeonController())->show(...))->name('api.v1.combatlog.dungeon.show');
    });
});
