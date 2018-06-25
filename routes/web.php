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
    Route::get('dungeonroute/{id}', 'DungeonRouteController@edit')->name('dungeonroute.edit');

    Route::post('dungeonroute/new', 'DungeonRouteController@savenew')->name('dungeonroute.savenew');
    Route::patch('dungeonroute/{id}', 'DungeonRouteController@update')->name('dungeonroute.update');

    Route::get('dungeonroutes', 'DungeonRouteController@view')->name('dungeonroutes');
});

Route::group(['middleware' => ['auth', 'role:admin']], function () {
    // Dungeons
    Route::get('admin/dungeon/new', 'DungeonController@new')->name('admin.dungeon.new');
    Route::get('admin/dungeon/{id}', 'DungeonController@edit')->name('admin.dungeon.edit');

    Route::post('admin/dungeon/new', 'DungeonController@savenew')->name('admin.dungeon.savenew');
    Route::patch('admin/dungeon/{id}', 'DungeonController@update')->name('admin.dungeon.update');

    Route::get('admin/dungeons', 'DungeonController@view')->name('admin.dungeons');

    // Floors
    Route::get('admin/floor/new', 'FloorController@newfloor')->name('admin.floor.new')->where(['dungeon' => '[0-9]+']);
    Route::get('admin/floor/{id}', 'FloorController@editfloor')->name('admin.floor.edit');

    Route::post('admin/floor/new', 'FloorController@savenew')->name('admin.floor.savenew')->where(['dungeon' => '[0-9]+']);
    Route::patch('admin/floor/{id}', 'FloorController@update')->name('admin.floor.update');

    // Expansions
    Route::get('admin/expansion/new', 'ExpansionController@new')->name('admin.expansion.new');
    Route::get('admin/expansion/{id}', 'ExpansionController@edit')->name('admin.expansion.edit');

    Route::post('admin/expansion/new', 'ExpansionController@savenew')->name('admin.expansion.savenew');
    Route::patch('admin/expansion/{id}', 'ExpansionController@update')->name('admin.expansion.update');

    Route::get('admin/expansions', 'ExpansionController@view')->name('admin.expansions');

    // NPCs
    Route::get('admin/npc/new', 'NpcController@new')->name('admin.npc.new');
    Route::get('admin/npc/{id}', 'NpcController@edit')->name('admin.npc.edit');

    Route::post('admin/npc/new', 'NpcController@savenew')->name('admin.npc.savenew');
    Route::patch('admin/npc/{id}', 'NpcController@update')->name('admin.npc.update');

    Route::get('admin/npcs', 'NpcController@view')->name('admin.npcs');
});