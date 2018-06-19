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
        Route::get('/enemypacks', 'EnemyPackController@list');
        Route::post('/enemypack', 'EnemyPackController@store');
        Route::delete('/enemypack', 'EnemyPackController@delete');
    // });
});
