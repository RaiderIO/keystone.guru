<?php

use App\Http\Controllers\Api\V1\InternalTeam\Cache\APICacheController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogEnemyFailureController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogEnemyResolutionController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogObservationController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogRouteController;
use App\Http\Controllers\Api\V1\InternalTeam\Combatlog\APICombatLogRunController;
use App\Http\Controllers\Api\V1\InternalTeam\Patreon\APIPatreonDiagnosticsController;
use App\Http\Controllers\Api\V1\InternalTeam\WalkGraph\APIWalkGraphController;
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
        Route::middleware(['api_scope:combatlog:write', 'throttle:api-combatlog-create-dungeonroute'])->prefix('route')->group(static function () {
            Route::post('/', [APICombatLogController::class, 'store'])->name('api.v1.combatlog.route.store');
        });
        Route::middleware(['api_scope:combatlog:write', 'throttle:api-combatlog-correct-event'])->prefix('event')->group(static function () {
            Route::post('correct', [APICombatLogController::class, 'correctEvents'])->name('api.v1.combatlog.event.correct');
        });

        // Read-only endpoints an AI agent needs for combat log triage (#4227) - admins and agents, nobody else
        Route::middleware(['api_role:admin|ai_agent', 'api_scope:combatlog:read'])->group(static function () {
            Route::get('seasons/{season}/runs/{runId}/segments', [APICombatLogRunController::class, 'segments'])->name('api.v1.combatlog.run.segments');
            Route::get('enemy-failures/{dungeon}', [APICombatLogEnemyFailureController::class, 'index'])->name('api.v1.combatlog.enemy_failures.index');
            Route::get('enemy-resolutions/{dungeon}', [APICombatLogEnemyResolutionController::class, 'index'])->name('api.v1.combatlog.enemy_resolutions.index');
            Route::get('route/{dungeonRoute}/post-body', [APICombatLogRouteController::class, 'postBody'])->name('api.v1.combatlog.route.post_body');
            Route::prefix('observations')->group(static function () {
                Route::middleware('throttle:api-combatlog-observations-density')->get('density', [APICombatLogObservationController::class, 'density'])->name('api.v1.combatlog.observations.density');
                Route::get('spells/{spell}', [APICombatLogObservationController::class, 'spellHistory'])->name('api.v1.combatlog.observations.spells.show');
                Route::get('npcs/{npc}', [APICombatLogObservationController::class, 'npcHistory'])->name('api.v1.combatlog.observations.npcs.show');
            });
        });
    });

    Route::prefix('route')->middleware('api_scope:routes:read,routes:write')->group(static function () {
        Route::get('/', [APIDungeonRouteController::class, 'index'])->name('api.v1.route.index');
        Route::prefix('{dungeonRoute}')->middleware('can:view,dungeonRoute')->group(static function () {
            Route::get('/', [APIDungeonRouteController::class, 'show'])->name('api.v1.route.show');

            Route::middleware(['api_scope:routes:write', 'throttle:api-create-dungeonroute-thumbnail'])->group(static function () {
                Route::post('/thumbnail', [APIDungeonRouteController::class, 'storeThumbnails'])->name('api.v1.route.thumbnail.store');
            });
        });
        Route::get('/thumbnailJob/{dungeonRouteThumbnailJob}', [APIDungeonRouteThumbnailJobController::class, 'show'])->name('api.v1.thumbnailjob.show');
    });

    Route::middleware(['api_role:admin|ai_agent', 'api_scope:admin'])->prefix('patreon')->group(static function () {
        // Reads the recorded run history and nothing else - no Patreon traffic, so no throttle
        Route::get('sync-runs', [APIPatreonDiagnosticsController::class, 'syncRuns'])->name('api.v1.patreon.sync_runs');

        // Everything below walks the whole campaign - the per-user endpoint included - and shares Patreon's
        // rate limit with the hourly sync
        Route::middleware('throttle:api-patreon-diagnostics')->group(static function () {
            Route::get('user', [APIPatreonDiagnosticsController::class, 'user'])->name('api.v1.patreon.user');
            Route::get('campaign', [APIPatreonDiagnosticsController::class, 'campaign'])->name('api.v1.patreon.campaign');
            Route::get('sync-dry-run', [APIPatreonDiagnosticsController::class, 'syncDryRun'])->name('api.v1.patreon.sync_dry_run');
            Route::get('benefit-reconciliation', [APIPatreonDiagnosticsController::class, 'benefitReconciliation'])->name('api.v1.patreon.benefit_reconciliation');
        });
    });

    Route::middleware(['api_role:admin|ai_agent', 'api_scope:admin', 'throttle:api-walkgraphs'])->prefix('walkgraphs')->group(static function () {
        Route::get('/', [APIWalkGraphController::class, 'index'])->name('api.v1.walkgraphs.index');
        Route::get('{uiMapId}', [APIWalkGraphController::class, 'show'])->whereNumber('uiMapId')->name('api.v1.walkgraphs.show');
    });

    Route::middleware(['api_role:admin', 'api_scope:admin'])->prefix('cache')->group(static function () {
        Route::post('drop', [APICacheController::class, 'drop'])->name('api.v1.cache.drop');
    });

    Route::prefix('routes/{gameVersion}')->middleware('api_scope:routes:read,routes:write')->group(static function () {
        Route::get('popular', [APIDungeonRouteDiscoverController::class, 'popular'])->name('api.v1.discover.popular');
        Route::get('new', [APIDungeonRouteDiscoverController::class, 'newest'])->name('api.v1.discover.new');
        Route::prefix('{dungeon}')->group(static function () {
            Route::get('popular', [APIDungeonRouteDiscoverController::class, 'dungeonPopular'])->name('api.v1.discover.dungeon.popular');
            Route::get('new', [APIDungeonRouteDiscoverController::class, 'dungeonNew'])->name('api.v1.discover.dungeon.new');
        });
    });

    // Static data
    Route::prefix('dungeon')->group(static function () {
        Route::get('/', [APIDungeonController::class, 'index'])->name('api.v1.combatlog.dungeon.index');
        Route::get('/{dungeon}', [APIDungeonController::class, 'show'])->name('api.v1.combatlog.dungeon.show');
    });
});

Route::fallback(
    // Render your 404 page, but now with web middleware (sessions) active
    fn() => response()->json(['error' => 'Not Found'], 404),
)->middleware('web');
