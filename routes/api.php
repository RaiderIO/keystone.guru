<?php

use App\Http\Controllers\Api\V1\APICombatLogController;

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
});
