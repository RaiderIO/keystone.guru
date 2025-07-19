<?php

use App\Models\Laratrust\Role;
use App\Models\User;

/**
 * @var User    $user
 * @var boolean $link
 * @var boolean $showAnonIcon
 * @var boolean $showRaiderIOStaffImage
 */

$link                   ??= false;
$showAnonIcon           ??= true;
$showRaiderIOStaffImage ??= true;
$isRaiderIOStaff        = $showRaiderIOStaffImage && isset($user) && $user->hasRole(Role::ROLE_ADMIN);
?>

@isset($user)
    @if ($link)
        @component('common.user.link', ['user' => $user])
            @includeWhen($isRaiderIOStaff, 'common.user.raideriostaffimage', ['user' => $user])
            @includeWhen(!$isRaiderIOStaff, 'common.user.userimage', ['user' => $user, 'showAnonIcon' => $showAnonIcon])
        @endcomponent
    @else
        @includeWhen($isRaiderIOStaff, 'common.user.raideriostaffimage', ['user' => $user])
        @includeWhen(!$isRaiderIOStaff, 'common.user.userimage', ['user' => $user, 'showAnonIcon' => $showAnonIcon])
    @endif
@endisset
