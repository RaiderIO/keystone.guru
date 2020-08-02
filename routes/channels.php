<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

//Broadcast::channel('route-edit.{dungeonroute}', function ($user, \App\Models\DungeonRoute $dungeonroute) {
////    return $dungeonroute->team !== null && $dungeonroute->team->isUserCollaborator($user);
////});

// https://laravel.com/docs/5.8/broadcasting#presence-channels
Broadcast::channel(sprintf('%s-route-edit.{dungeonroute}', env('APP_TYPE')), function (\App\User $user, \App\Models\DungeonRoute $dungeonroute)
{
    $result = false;
    if ($dungeonroute->published || $dungeonroute->author_id === $user->id) {
        $result = ['name' => $user->name, 'color' => $user->echo_color];
    }
    return $result;
});

Broadcast::channel(sprintf('%s-dungeon-edit.{dungeon}', env('APP_TYPE')), function (\App\User $user, \App\Models\Dungeon $dungeon)
{
    $result = true;
//    $result = false;
//    if ($user->hasRole('admin')) {
//        $result = ['name' => $user->name, 'color' => $user->echo_color];
//    }
    return $result;
});