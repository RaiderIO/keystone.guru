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

use App\Models\DungeonRoute;

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');
Route::get('dungeonroute/new', 'DungeonRouteController@new')->name('dungeonroute.new');
Route::post('dungeonroute/new', 'DungeonRouteController@savenew')->name('dungeonroute.savenew');
Route::get('dungeonroutes', 'DungeonRouteController@list')->name('dungeonroutes');

// Edit your own dungeon routes
Route::get('dungeonroute/{dungeonroute}', 'DungeonRouteController@edit')
    ->middleware('can:edit,dungeonroute')
    ->name('dungeonroute.edit');
// Submit a patch for your own dungeon route
Route::patch('dungeonroute/{dungeonroute}', 'DungeonRouteController@update')
    ->middleware('can:edit,dungeonroute')
    ->name('dungeonroute.update');

// ['auth', 'role:admin|user']

Route::get('profile/(user}', 'ProfileController@view')->name('profile.view');

Route::group(['middleware' => ['auth', 'role:user|admin']], function () {
    Route::get('profile', 'ProfileController@edit')->name('profile.edit');
    Route::patch('profile/{user}', 'ProfileController@update')->name('profile.update');
    Route::patch('profile', 'ProfileController@changepassword')->name('profile.changepassword');
});

Route::group(['middleware' => ['auth', 'role:admin']], function () {
    // Only admins may view a list of profiles
    Route::get('profiles', 'ProfileController@list')->name('profile.list');

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
    Route::get('admin/expansion/{expansion}', 'ExpansionController@edit')->name('admin.expansion.edit');

    Route::post('admin/expansion/new', 'ExpansionController@savenew')->name('admin.expansion.savenew');
    Route::patch('admin/expansion/{expansion}', 'ExpansionController@update')->name('admin.expansion.update');

    Route::get('admin/expansions', 'ExpansionController@list')->name('admin.expansions');

    // NPCs
    Route::get('admin/npc/new', 'NpcController@new')->name('admin.npc.new');
    Route::get('admin/npc/{id}', 'NpcController@edit')->name('admin.npc.edit');

    Route::post('admin/npc/new', 'NpcController@savenew')->name('admin.npc.savenew');
    Route::patch('admin/npc/{id}', 'NpcController@update')->name('admin.npc.update');

    Route::get('admin/npcs', 'NpcController@view')->name('admin.npcs');
});