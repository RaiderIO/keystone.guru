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
    Route::get('admin/dungeon/new', 'DungeonController@new')->name('admin.dungeon.new');
    Route::post('admin/dungeon/new', 'DungeonController@store')->name('admin.dungeon.store');

    Route::get('admin/dungeons', 'DungeonController@view')->name('admin.dungeons');


    Route::get('admin/expansion/new', 'ExpansionController@new')->name('admin.expansion.new');
    Route::post('admin/expansion/new', 'ExpansionController@store')->name('admin.expansion.store');

    Route::get('admin/expansions', 'ExpansionController@view')->name('admin.expansions');
});