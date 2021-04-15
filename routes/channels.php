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
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\User;
use Faker\Provider\Color;

Broadcast::channel(sprintf('%s-route-edit.{dungeonroute}', env('APP_TYPE')), function (?User $user, DungeonRoute $dungeonroute)
{
    $result = false;

    if (Auth::check()) {
        if ($user->echo_anonymous &&
            // If we didn't create this route, don't show our name
            $dungeonroute->author_id !== $user->id &&
            // If the route is now not part of a team, OR if we're not a member of the team, we're anonymous
            ($dungeonroute->team === null || ($dungeonroute->team !== null && !$dungeonroute->team->isUserMember($user)))) {

            $result = [
                'id'        => random_int(158402, 99999999),
                'name'      => sprintf('Anonymous %s', collect(config('keystoneguru.echo.randomsuffixes'))->random()),
                'color'     => Color::hexColor(),
                'anonymous' => true,
            ];
        } else {
            $result = [
                'id'         => $user->id,
                'name'       => $user->name,
                'initials'   => $user->initials,
                'color'      => $user->echo_color,
                'avatar_url' => optional($user->iconfile)->getURL(),
                'anonymous'  => false,
            ];
        }
    }

    return $result;
});

Broadcast::channel(sprintf('%s-dungeon-edit.{dungeon}', env('APP_TYPE')), function (User $user, Dungeon $dungeon)
{
    $result = false;
    if ($user->hasRole('admin')) {
        $result = [
            'id'         => $user->id,
            'name'       => $user->name,
            'initials'   => $user->initials,
            'color'      => $user->echo_color,
            'avatar_url' => optional($user->iconfile)->getURL(),
            'anonymous'  => false,
        ];
    }
    return $result;
});