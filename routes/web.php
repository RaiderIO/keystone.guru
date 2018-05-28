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
    Route::get('admin/dungeon/{id}', 'DungeonController@edit')->name('admin.dungeon.edit');

    Route::post('admin/dungeon/new', 'DungeonController@savenew')->name('admin.dungeon.savenew');
    Route::patch('admin/dungeon/{id}', 'DungeonController@update')->name('admin.dungeon.update');

    Route::get('admin/dungeons', 'DungeonController@view')->name('admin.dungeons');


    Route::get('admin/expansion/new', 'ExpansionController@new')->name('admin.expansion.new');
    Route::get('admin/expansion/{id}', 'ExpansionController@edit')->name('admin.expansion.edit');

    Route::post('admin/expansion/new', 'ExpansionController@savenew')->name('admin.expansion.savenew');
    Route::patch('admin/expansion/{id}', 'ExpansionController@update')->name('admin.expansion.update');

    Route::get('admin/expansions', 'ExpansionController@view')->name('admin.expansions');
});