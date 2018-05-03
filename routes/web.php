<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Auth::routes();

Route::get('/', 'HomeController@index')->name('home');
Route::get('dungeonroute/new', 'DungeonRouteController@new')->name('dungeonroute.new');
// ['auth', 'role:admin|user']

Route::group(['middleware' => ['auth', 'role:user|admin']], function () {
    Route::get('dungeonroute/new', 'DungeonRouteController@new')->name('dungeonroute.new');
});

Route::group(['middleware' => ['auth', 'role:admin']], function () {
    Route::get('dungeon/new', 'DungeonController@new')->name('dungeon.new');
    Route::post('dungeon/new', 'DungeonController@store')->name('dungeon.store');
});