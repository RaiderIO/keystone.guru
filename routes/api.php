<?php

use App\Http\Controllers\Api\V1\InternalTeam\Cache\APICacheController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogParseFailureController;
use App\Http\Controllers\Api\V1\Public\Dungeon\APIDungeonController;
use App\Http\Controllers\Api\V1\Public\Route\APIDungeonRouteController;
use App\Http\Controllers\Api\V1\Public\Route\APIDungeonRouteDiscoverController;
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
            Route::post('/', new APICombatLogController()->store(...))->name('api.v1.combatlog.route.store');
        });
        Route::middleware('throttle:api-combatlog-correct-event')->prefix('event')->group(static function () {
            Route::post('correct', new APICombatLogController()->correctEvents(...))->name('api.v1.combatlog.event.correct');
        });

        Route::middleware(['api_role:admin'])->prefix('parse-failures')->group(static function () {
            Route::get('/', new APICombatLogParseFailureController()->index(...))->name('api.v1.combatlog.parsefailures.index');
            Route::get('/{parseFailure}/segments', new APICombatLogParseFailureController()->segments(...))->name('api.v1.combatlog.parsefailures.segments');
            Route::post('/{parseFailure}/resolve', new APICombatLogParseFailureController()->resolve(...))->name('api.v1.combatlog.parsefailures.resolve');
        });
    });

    Route::prefix('route')->group(static function () {
        Route::get('/', new APIDungeonRouteController()->index(...))->name('api.v1.route.index');
        Route::prefix('{dungeonRoute}')->middleware('can:view,dungeonRoute')->group(static function () {
            Route::get('/', new APIDungeonRouteController()->show(...))->name('api.v1.route.show');

            Route::middleware('throttle:api-create-dungeonroute-thumbnail')->group(static function () {
                Route::post('/thumbnail', new APIDungeonRouteController()->storeThumbnails(...))->name('api.v1.route.thumbnail.store');
            });
        });
        Route::get('/thumbnailJob/{dungeonRouteThumbnailJob}', new APIDungeonRouteThumbnailJobController()->show(...))->name('api.v1.thumbnailjob.show');
    });

    Route::middleware(['api_role:admin'])->prefix('cache')->group(static function () {
        Route::post('drop', new APICacheController()->drop(...))->name('api.v1.cache.drop');
    });

    Route::prefix('routes/{gameVersion}')->group(static function () {
        Route::get('popular', new APIDungeonRouteDiscoverController()->popular(...))->name('api.v1.discover.popular');
        Route::get('new', new APIDungeonRouteDiscoverController()->newest(...))->name('api.v1.discover.new');
        Route::prefix('{dungeon}')->group(static function () {
            Route::get('popular', new APIDungeonRouteDiscoverController()->dungeonPopular(...))->name('api.v1.discover.dungeon.popular');
            Route::get('new', new APIDungeonRouteDiscoverController()->dungeonNew(...))->name('api.v1.discover.dungeon.new');
        });
    });

    // Static data
    Route::prefix('dungeon')->group(static function () {
        Route::get('/', new APIDungeonController()->index(...))->name('api.v1.combatlog.dungeon.index');
        Route::get('/{dungeon}', new APIDungeonController()->show(...))->name('api.v1.combatlog.dungeon.show');
    });
});

Route::fallback(
    // Render your 404 page, but now with web middleware (sessions) active
    fn() => response()->json(['error' => 'Not Found'], 404),
)->middleware('web');
