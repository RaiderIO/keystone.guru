<?php

use App\Http\Controllers\Api\V1\InternalTeam\Cache\APICacheController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogEnemyFailureController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogRouteController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogRunController;
use App\Http\Controllers\Api\V1\InternalTeam\Patreon\APIPatreonDiagnosticsController;
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

        // Read-only endpoints an AI agent needs for combat log triage (#4227) - admins and agents, nobody else
        Route::middleware(['api_role:admin|ai_agent'])->group(static function () {
            Route::get('seasons/{season}/runs/{runId}/segments', new APICombatLogRunController()->segments(...))->name('api.v1.combatlog.run.segments');
            Route::get('enemy-failures/{dungeon}', new APICombatLogEnemyFailureController()->index(...))->name('api.v1.combatlog.enemy_failures.index');
            Route::get('route/{dungeonRoute}/post-body', new APICombatLogRouteController()->postBody(...))->name('api.v1.combatlog.route.post_body');
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

    Route::middleware(['api_role:admin|ai_agent'])->prefix('patreon')->group(static function () {
        // Reads the recorded run history and nothing else - no Patreon traffic, so no throttle
        Route::get('sync-runs', new APIPatreonDiagnosticsController()->syncRuns(...))->name('api.v1.patreon.sync_runs');

        // Everything below walks the whole campaign - the per-user endpoint included - and shares Patreon's
        // rate limit with the hourly sync
        Route::middleware('throttle:api-patreon-diagnostics')->group(static function () {
            Route::get('user', new APIPatreonDiagnosticsController()->user(...))->name('api.v1.patreon.user');
            Route::get('campaign', new APIPatreonDiagnosticsController()->campaign(...))->name('api.v1.patreon.campaign');
            Route::get('sync-dry-run', new APIPatreonDiagnosticsController()->syncDryRun(...))->name('api.v1.patreon.sync_dry_run');
            Route::get('benefit-reconciliation', new APIPatreonDiagnosticsController()->benefitReconciliation(...))->name('api.v1.patreon.benefit_reconciliation');
        });
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
