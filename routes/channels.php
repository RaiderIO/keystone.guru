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

// https://laravel.com/docs/8.x/broadcasting#presence-channels
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\LiveSession;
use App\User;

$dungeonRouteChannelCallback = function (?User $user, DungeonRoute $dungeonroute)
{
    $result = false;

    if (Auth::check()) {
        if ($user->echo_anonymous &&
            // If we didn't create this route, don't show our name
            $dungeonroute->author_id !== $user->id &&
            // If the route is now not part of a team, OR if we're not a member of the team, we're anonymous
            ($dungeonroute->team === null || ($dungeonroute->team !== null && !$dungeonroute->team->isUserMember($user)))) {

            $randomName = collect(config('keystoneguru.echo.randomsuffixes'))->random();

            $result = [
                'public_key' => $user->public_key,
                'name'       => sprintf('Anonymous %s', $randomName),
                'initials'   => initials($randomName),
                // https://stackoverflow.com/a/9901154/771270
                'color'      => randomHexColor(),
                'avatar_url' => null,
                'anonymous'  => true,
                'url'        => '#',
            ];
        } else {
            $result = [
                'public_key' => $user->public_key,
                'name'       => $user->name,
                'initials'   => $user->initials,
                'color'      => $user->echo_color,
                'avatar_url' => optional($user->iconfile)->getURL(),
                'anonymous'  => false,
                'url'        => route('profile.view', $user)
            ];
        }
    }

    return $result;
};

Broadcast::channel(sprintf('%s-route-edit.{dungeonroute}', config('app.type')), $dungeonRouteChannelCallback);
Broadcast::channel(sprintf('%s-live-session.{livesession}', config('app.type')), function (?User $user, LiveSession $livesession) use ($dungeonRouteChannelCallback)
{
    // Validate live sessions the same way as a dungeon route
    return $dungeonRouteChannelCallback($user, $livesession->dungeonroute);
});

Broadcast::channel(sprintf('%s-dungeon-edit.{dungeon}', config('app.type')), function (User $user, Dungeon $dungeon)
{
    $result = false;
    if ($user->hasRole('admin')) {
        $result = [
            'public_key' => $user->public_key,
            'name'       => $user->name,
            'initials'   => $user->initials,
            'color'      => $user->echo_color,
            'avatar_url' => optional($user->iconfile)->getURL(),
            'anonymous'  => false,
        ];
    }
    return $result;
});